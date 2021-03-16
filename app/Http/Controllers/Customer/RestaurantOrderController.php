<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\StringHelper;
use App\Helpers\ResponseHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatus;
use App\Models\RestaurantOrderContact;
use App\Models\RestaurantOrderItem;
use App\Models\Menu;

class RestaurantOrderController extends Controller
{
    use StringHelper, ResponseHelper;

    public function index()
    {
        $customer_id = Auth::guard('customers')->user()->id;
        $restaurantOrders = RestaurantOrder::with('restaurant')
            ->with('restaurantBranch')
            ->with('RestaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('customer_id', $customer_id)
            ->latest()
            ->paginate(10)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function show($slug)
    {
        $customer_id = Auth::guard('customers')->user()->id;
        $order = RestaurantOrder::with('restaurant')
            ->with('restaurantBranch')
            ->with('RestaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->where('customer_id', $customer_id)
            ->firstOrFail();

        return $this->generateResponse($order, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validator = $this->validateOrder($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = Auth::guard('customers')->user()->id;

        $restaurantBranch = $this->getRestaurantBranch($validatedData['restaurant_branch_slug']);

        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;

        $order = RestaurantOrder::create($validatedData);
        $orderId = $order->id;

        $this->createOrderStatus($orderId);
        $this->createOrderContact($orderId, $validatedData['customer_info']);
        $this->createOrderItems($orderId, $validatedData['order_items']);

        return $this->generateResponse($order->refresh()->load('restaurant', 'restaurantBranch', 'restaurantOrderContact', 'restaurantOrderItems'), 201);
    }

    public function destroy($slug)
    {
        $order = RestaurantOrder::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, TRUE);
        }

        $this->createOrderStatus($order->id, 'cancelled');
        return $this->generateResponse('The order has successfully been cancelled.', 200, TRUE);
    }

    private function validateOrder($request)
    {
        return Validator::make($request->all(), [
            'slug' => 'required|unique:orders',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'customer_info.house_number' => 'required|string',
            'customer_info.floor' => 'nullable|string',
            'customer_info.street_name' => 'required|string',
            'customer_info.latitude' => 'nullable|numeric',
            'customer_info.longitude' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.menu_slug' => 'required|exists:App\Models\Menu,slug',
            'order_items.*.menu_name' => 'required|string',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.amount' => 'required|numeric',
            'order_items.*.tax' => 'required|numeric',
            'order_items.*.discount' => 'required|numeric',
            'order_items.*.variations' => 'required|array',
            'order_items.*.toppings' => 'required|array',
            'order_items.*.variations.*.name' => 'required|string',
            'order_items.*.variations.*.value' => 'required|string',
            'order_items.*.toppings.*.name' => 'required|string',
            'order_items.*.toppings.*.value' => 'required|string',
        ]);
    }

    private function createOrderStatus($orderId, $status = 'pending')
    {
        RestaurantOrderStatus::create([
            'status' => $status,
            'restaurant_order_id' => $orderId,
        ]);
    }

    private function createOrderContact($orderId, $customerInfo)
    {
        $customerInfo['restaurant_order_id'] = $orderId;
        RestaurantOrderContact::create($customerInfo);
    }

    private function createOrderItems($orderId, $orderItems)
    {
        foreach ($orderItems as $item) {
            $item['restaurant_order_id'] = $orderId;
            $item['menu_id'] = $this->getMenuId($item['menu_slug']);
            $item['variations'] = $item['variations'];
            $item['toppings'] = $item['toppings'];

            RestaurantOrderItem::create($item);
        }
    }

    private function getRestaurantBranch($slug)
    {
        return RestaurantBranch::where('slug', $slug)->first();
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }
}
