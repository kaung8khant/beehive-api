<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ForbiddenException;
use App\Helpers\KbzPayHelper;
use App\Helpers\OrderAssignHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Services\MessagingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestaurantOrderController extends Controller
{
    use PromocodeHelper, ResponseHelper, StringHelper, OrderAssignHelper;

    protected $customer;
    protected $messageService;

    public function __construct(MessagingService $messageService)
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }

        $this->messageService = $messageService;
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

        try {
            OrderHelper::checkOpeningTime($validatedData['restaurant_branch_slug']);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 403, true);
        }

        $validatedData['customer_id'] = $this->customer->id;
        $validatedData['order_date'] = Carbon::now();
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

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$customer->phone_number], $message, 'order', $smsData, $this->messageService);
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
            return $order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems');
        });
        $this->assignOrder('restaurant', $order->slug);

        OrderHelper::notifySystem($order, $this->customer->phone_number, $this->messageService);

        return $order;
    }
}
