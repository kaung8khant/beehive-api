<?php

namespace App\Http\Controllers\Customer\v3;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Promocode;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
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
        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('customer_id', $this->customer->id)
            ->orderBy('id', 'desc')
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = OrderHelper::validateOrderV3($request);

        if (gettype($validatedData) == 'string') {
            return $this->generateResponse($validatedData, 422, true);
        }

        $validatedData['customer_id'] = $this->customer->id;

        $validatedData = OrderHelper::prepareRestaurantVariants($validatedData);

        if ($validatedData['promo_code']) {
            $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();
            if (!$promocode) {
                return $this->generateResponse('Promocode not found', 422, true);
            }

            $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'restaurant');
            if (!$validUsage) {
                return $this->generateResponse('Invalid promocode usage for restaurant.', 422, true);
            }

            $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $this->customer, 'restaurant');
            if (!$validRule) {
                return $this->generateResponse('Invalid promocode.', 422, true);
            }

            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'restaurant');

            $validatedData['promocode_id'] = $promocode->id;
            $validatedData['promocode'] = $promocode->code;
            $validatedData['promocode_amount'] = $promocodeAmount;
        }

        //commission
        $restaurantBranch = RestaurantBranch::where('slug', $validatedData['restaurant_branch_slug'])->firstOrFail();
        $restaurant = Restaurant::where('id', $restaurantBranch->restaurant_id)->firstOrFail();
        if ($restaurant->commission>0) {
            $validatedData['commission']=($validatedData['subTotal']+$validatedData['tax']) * $restaurant->commission * 0.01;

            if ($validatedData['promo_code']) {
                $validatedData['commission']=($validatedData['subTotal']+$validatedData['tax']-$validatedData['promocode_amount']) * $restaurant->commission * 0.01;
            }
        }

        $order = DB::transaction(function () use ($validatedData) {
            $order = RestaurantOrder::create($validatedData);
            OrderHelper::createOrderStatus($order->id);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createOrderItems($order->id, $validatedData['order_items']);
            return $order;
        });

        $this->notifySystem($validatedData['restaurant_branch_slug'], $order->slug);

        return $this->generateResponse($order->refresh(), 201, );
    }

    public function show($slug)
    {
        $order = RestaurantOrder::with('RestaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->where('customer_id', $this->customer->id)
            ->firstOrFail();

        return $this->generateResponse($order, 200);
    }

    public function destroy($slug)
    {
        $order = RestaurantOrder::where('slug', $slug)
            ->where('customer_id', $this->customer->id)
            ->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $this->notify(
            $order->restaurantBranch->slug,
            [
                'title' => 'Order cancelled',
                'body' => "Restaurant order just has been updated",
                'type' => 'update',
                'slug' => $order->slug,
                'status' => 'cancelled',
            ]
        );

        $message = 'Your order has successfully been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$this->customer->phone_number], $message, 'order', $smsData);
        OrderHelper::createOrderStatus($order->id, 'cancelled');

        return $this->generateResponse($message, 200, true);
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
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
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
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );
    }
}
