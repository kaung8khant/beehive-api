<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\KbzPayHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestaurantOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function index(Request $request)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function show($slug)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $order = RestaurantOrder::with('RestaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        return $this->generateResponse($order, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = OrderHelper::validateOrder($request);

        if (gettype($validatedData) == 'string') {
            return $this->generateResponse($validatedData, 422, true);
        }

        $validatedData['customer_id'] = $this->customer->id;
        $validatedData = OrderHelper::prepareRestaurantVariations($validatedData);

        if ($validatedData['promo_code']) {
            $validatedData = $this->getPromoData($validatedData);
        }

        if ($validatedData['payment_mode'] === 'KPay') {
            $kPayData = KbzPayHelper::createKbzPay($validatedData, 'restaurant');

            if (!$kPayData || $kPayData['Response']['code'] != '0' || $kPayData['Response']['result'] != 'SUCCESS') {
                return $this->generateResponse('Error connecting to KBZ Pay service.', 500, true);
            }
        }

        $order = $this->restaurantOrderTransaction($validatedData);

        if ($validatedData['payment_mode'] === 'KPay') {
            $order['prepay_id'] = $kPayData['Response']['prepay_id'];
        }

        OrderHelper::sendAdminPushNotifications();
        OrderHelper::sendVendorPushNotifications($validatedData['restaurant_branch_id']);

        OrderHelper::sendAdminSms();
        OrderHelper::sendVendorSms($validatedData['restaurant_branch_id']);

        $message = 'Your order has successfully been created.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = $this->customer->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);

        return $this->generateResponse($order, 201);
    }

    public function destroy($slug)
    {
        $customer = Auth::guard('customers')->user();
        $customerId = $customer->id;
        $order = RestaurantOrder::where('customer_id', $customerId)->where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $this->notify(
            $order->restaurantBranch->slug,
            [
                'title' => 'Order cancelled',
                'body' => 'Restaurant order just has been updated',
                'type' => 'update',
                'slug' => $order->slug,
                'status' => 'cancelled',
            ]
        );

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$customer->phone_number], $message, 'order', $smsData);
        OrderHelper::createOrderStatus($order->id, 'cancelled');

        return $this->generateResponse($message, 200, true);
    }

    private function getPromoData($validatedData)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();
        if (!isset($promocode) && empty($promocode)) {
            return $this->generateResponse('Promocode not found', 422, true);
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'restaurant');
        if (!$validUsage) {
            return $this->generateResponse('Invalid promocode usage for restaurant.', 422, true);
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $this->customer, 'restaurant');
        if (!$validRule) {
            return $this->generateResponse('Invalid promocode rule.', 422, true);
        }
        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'restaurant');

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = $promocodeAmount;

        return $validatedData;
    }

    private function restaurantOrderTransaction($validatedData)
    {
        $order = DB::transaction(function () use ($validatedData) {
            $order = RestaurantOrder::create($validatedData);
            OrderHelper::createOrderStatus($order->id);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createOrderItems($order->id, $validatedData['order_items']);
            return $order;
        });

        $this->notifySystem($validatedData['restaurant_branch_slug'], $order->slug);

        return $order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems');
    }

    private function notifySystem($branchSlug, $slug)
    {
        $this->notify(
            $branchSlug,
            [
                'title' => 'New Order',
                'body' => "You've just recevied new order. Check now!",
                'type' => 'create',
                'restaurantOrder' => RestaurantOrder::with('RestaurantOrderContact')
                    ->with('RestaurantOrderItems')
                    ->where('slug', $slug)
                    ->firstOrFail(),
            ]
        );
    }

    private function notify($slug, $data)
    {
        $this->notifyRestaurant(
            $slug,
            [
                'title' => $data['title'] . 'client',
                'body' => $data['body'],
                'data' => [
                    'action' => $data['type'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : '',
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : '',
                    'slug' => !empty($data['slug']) ? $data['slug'] : '',
                ],
            ]
        );

        $this->notifyAdmin(
            [
                'title' => $data['title'] . 'client',
                'body' => $data['body'],
                'data' => [
                    'action' => $data['type'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : '',
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : '',
                    'slug' => !empty($data['slug']) ? $data['slug'] : '',
                ],
            ]
        );
    }
}
