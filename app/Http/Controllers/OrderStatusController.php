<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderStatus;
use App\Models\Order;

class OrderStatusController extends Controller
{
    public function index($slug)
    {
        $orderStatuses = OrderStatus::whereHas('order', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->latest()->get();

        return $orderStatuses;
    }

    public function store(Request $request, $slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return response()->json(['message' => 'The order has already been ' . $order->order_status . '.'], 406);
        }

        $request->validate([
            'status' => 'required|in:pending,preparing,pickUp,onRoute,delivered,cancelled',
        ]);

        $orderStatus = OrderStatus::create([
            'order_id' => $order->id,
            'status' => $request->status,
            'created_by' => Auth::guard('users')->user()->name,
        ]);

        return response()->json($orderStatus, 200);
    }

    public function getLatestOrderStatus($slug)
    {
        return Order::where('slug', $slug)->firstOrFail()->order_status;
    }
}
