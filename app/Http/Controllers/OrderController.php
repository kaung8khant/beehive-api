<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\StringHelper;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderContact;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Menu;

class OrderController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return Order::orderBy('id', 'desc')
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateOrder($request);

        $order = Order::create($validatedData);
        $orderId = $order->id;

        $this->createOrderStatus($orderId);
        $this->createOrderContact($orderId, $validatedData['customer_info']);
        $this->createOrderItems($orderId, $validatedData['order_items'], $request->order_type);

        return response()->json($order->refresh()->load('order_contact', 'order_statuses', 'order_items'), 201);
    }

    public function show($slug)
    {
        $order = Order::with('order_contact')
            ->with(['order_statuses' => function ($query) {
                $query->orderBy('id', 'desc');
            }])
            ->with('order_items')
            ->where('slug', $slug)
            ->firstOrFail();
        return response()->json($order, 200);
    }

    public function update(Request $request, $slug)
    {
        // $order = Order::where('slug', $slug)->firstOrFail();

        // $validatedData = $request->validate([
        //     'special_instruction' => 'nullable',
        //     'order_date' => 'required|date_format:Y-m-d',
        //     'order_type' => 'required|in:restaurant,shop',
        //     'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
        //     'delivery_mode' => 'required|in:package,delivery',
        //     'rating' => 'required|in:1,2,3,4,5',
        // ]);

        // $order->update($validatedData);
        // return response()->json($order, 200);
    }

    public function destroy($slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();
        $latestOrderStatus = $order->order_statuses()->latest()->first()->status;

        if ($latestOrderStatus === 'cancelled') {
            return response()->json(['message' => 'The order has already been cancelled.'], 200);
        }

        $this->createOrderStatus($order->id, 'cancelled');
        return response()->json(['message' => 'Successfully cancelled.'], 200);
    }

    private function validateOrder(Request $request)
    {
        $rules = [
            'slug' => 'required|unique:orders',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'order_type' => 'required|in:restaurant,shop',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'customer_info' => 'required',
            'customer_info.customer_slug' => 'required|exists:App\Models\Customer,slug',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'customer_info.house_number' => 'required|string',
            'customer_info.floor' => 'nullable|string',
            'customer_info.street_name' => 'required|string',
            'customer_info.latitude' => 'nullable|numeric',
            'customer_info.longitude' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.item_slug' => '',
            'order_items.*.item_name' => 'required|string',
            'order_items.*.item_type' => 'required|in:product,menu',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.amount' => 'required|numeric',
            'order_items.*.tax' => 'required|numeric',
            'order_items.*.discount' => 'required|numeric',
        ];

        if ($request->order_type === 'restaurant') {
            $rules['order_items.*.item_slug'] = 'required|exists:App\Models\Menu,slug';
        } else {
            $rules['order_items.*.item_slug'] = 'required|exists:App\Models\Product,slug';
        }

        return $request->validate($rules);
    }

    private function createOrderStatus($orderId, $status = 'pending')
    {
        OrderStatus::create([
            'order_id' => $orderId,
            'status' => $status,
            'created_by' => Auth::guard('users')->user()->name,
        ]);
    }

    private function createOrderContact($orderId, $customerInfo)
    {
        $customerInfo['customer_id'] = $this->getCustomerId($customerInfo['customer_slug']);
        $customerInfo['order_id'] = $orderId;
        OrderContact::create($customerInfo);
    }

    private function createOrderItems($orderId, $orderItems, $orderType)
    {
        foreach ($orderItems as $item) {
            $item['order_id'] = $orderId;

            if ($orderType === 'restaurant') {
                $item['item_id'] = $this->getMenuId($item['item_slug']);
            } else {
                $item['item_id'] = $this->getProductId($item['item_slug']);
            }

            OrderItem::create($item);
        }
    }

    private function getCustomerId($slug)
    {
        return Customer::where('slug', $slug)->first()->id;
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }
}
