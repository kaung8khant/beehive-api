<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\CollectionHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\SmsHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\OrderAssignHelper;

class RestaurantOrderController extends Controller
{
    use  PromocodeHelper, ResponseHelper, StringHelper, OrderAssignHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        // $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
        //     ->whereHas('restaurantOrderContact', function ($q) use ($request) {
        //         $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
        //             ->orWhere('phone_number', $request->filter);
        //     })
        //     ->orWhere('slug', $request->filter)
        //     ->orderBy($sorting['orderBy'], $sorting['sortBy'])
        //     ->paginate($request->size)
        //     ->items();
        if ($request->filter) {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                ->orWhereHas('restaurant', function ($query) use ($request) {
                    $query->where('name', $request->filter);
                })
                ->orWhere('id', ltrim($request->filter, '0'))
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->get();
        } else {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->whereBetween('order_date', array($request->from, $request->to))
                ->get();
        }
        return $this->generateResponse($restaurantOrders, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = OrderHelper::validateOrderV3($request, true);

        if (gettype($validatedData) == 'string') {
            return $this->generateResponse($validatedData, 422, true);
        }

        $customer = Customer::where('slug', $validatedData['customer_slug'])->first();
        $validatedData['customer_id'] = $customer->id;

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

            $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'restaurant');
            if (!$validRule) {
                return $this->generateResponse('Invalid promocode.', 422, true);
            }

            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'restaurant');

            $validatedData['promocode_id'] = $promocode->id;
            $validatedData['promocode'] = $promocode->code;
            $validatedData['promocode_amount'] = $promocodeAmount;
        }

        $order = DB::transaction(function () use ($validatedData) {
            $order = RestaurantOrder::create($validatedData);
            OrderHelper::createOrderStatus($order->id);
            OrderHelper::createOrderContact($order->id, $validatedData['customer_info'], $validatedData['address']);
            OrderHelper::createOrderItems($order->id, $validatedData['order_items']);
            return $order;
        });

        $this->assignOrder('restaurant', $order->slug);

        $phoneNumber = Customer::where('id', $order->customer_id)->value('phone_number');
        OrderHelper::sendPushNotifications($order, $validatedData['restaurant_branch_id']);
        OrderHelper::sendSmsNotifications($validatedData['restaurant_branch_id'], $phoneNumber);

        return $this->generateResponse($order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems'), 201);
    }

    public function show(RestaurantOrder $restaurantOrder)
    {
        return $this->generateResponse($restaurantOrder->load('RestaurantOrderContact', 'RestaurantOrderItems'), 200);
    }

    public function destroy(RestaurantOrder $restaurantOrder)
    {
        if ($restaurantOrder->order_status === 'delivered' || $restaurantOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $restaurantOrder->order_status . '.', 406, true);
        }

        $message = 'Your order has been cancelled.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();
        $phoneNumber = Customer::where('id', $restaurantOrder->customer_id)->first()->phone_number;

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
        OrderHelper::createOrderStatus($restaurantOrder->id, 'cancelled');

        return $this->generateResponse('The order has successfully been cancelled.', 200, true);
    }

    public function changeStatus(Request $request, RestaurantOrder $restaurantOrder)
    {
        if ($restaurantOrder->order_status === 'delivered' || $restaurantOrder->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $restaurantOrder->order_status . '.', 406, true);
        }

        OrderHelper::createOrderStatus($restaurantOrder->id, $request->status);

        if ($request->status === 'cancelled') {
            $message = 'Your order has been cancelled.';
            $smsData = SmsHelper::prepareSmsData($message);
            $uniqueKey = StringHelper::generateUniqueSlug();
            $phoneNumber = Customer::where('id', $restaurantOrder->customer_id)->first()->phone_number;

            SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData);
        }

        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
    }

    public function getBranchOrders(Request $request, RestaurantBranch $restaurantBranch)
    {
        $vendorBranchId = Auth::guard('vendors')->user()->restaurant_branch_id;
        if ($vendorBranchId !== $restaurantBranch->id) {
            abort(404);
        }

        // $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);

        // $restaurantOrders = RestaurantOrder::with('restaurantOrderContact', 'RestaurantOrderItems')
        //     ->where('restaurant_branch_id', $restaurantBranch->id)
        //     ->where(function ($query) use ($request) {
        //         return $query->whereHas('restaurantOrderContact', function ($q) use ($request) {
        //             $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
        //                 ->orWhere('phone_number', $request->filter);
        //         })
        //             ->orWhere('slug', $request->filter);
        //     })
        //     ->orderBy($sorting['orderBy'], $sorting['sortBy'])
        //     ->paginate(10)
        //     ->items();

        // return $this->generateResponse($restaurantOrders, 200);


        $sorting = CollectionHelper::getSorting('restaurant_orders', 'id', $request->by ? $request->by : 'desc', $request->order);
        if ($request->filter) {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->where('restaurant_branch_id', $restaurantBranch->id)
                ->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                ->orWhere('id', ltrim($request->filter, '0'))
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->get();
        } else {
            $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact', 'RestaurantOrderItems')
                ->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->where('restaurant_branch_id', $restaurantBranch->id)
                ->whereBetween('order_date', array($request->from, $request->to))
                ->get();
        }
        return $this->generateResponse($restaurantOrders, 200);
    }

    public function cancelOrderItem(RestaurantOrder $restaurantOrder, RestaurantOrderItem $restaurantOrderItem)
    {
        $restaurantOrderItem->delete();
        $restaurantOrder = RestaurantOrder::where('slug', $restaurantOrder->slug)->first();

        $promocode = Promocode::where('code', $restaurantOrder->promocode)->first();
        $orderItems = $restaurantOrder->restaurantOrderItems;
        $subTotal = 0;
        $commission = 0;

        foreach ($orderItems as $item) {
            $amount = ($item->amount  - $item->discount) * $item->quantity;
            $subTotal += $amount;
            $commission += $item->commission;
        }
        $commission = $subTotal * $restaurantOrder->restaurant->commission * 0.01;

        if ($promocode->type === 'fix') {
            $restaurantOrder->update(['promocode_amount' => $promocode->amount, 'commission' => $commission]);
        } else {
            $restaurantOrder->update(['promocode_amount' => $subTotal * $promocode->amount * 0.01, 'commission' => $commission]);
        }
        return response()->json(['message' => 'Successfully cancelled.'], 200);
    }
}
