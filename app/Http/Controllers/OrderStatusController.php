<?php

namespace App\Http\Controllers;

use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter=$request->filter;
        return OrderStatus::with('order')
            ->where('created_by', 'LIKE', '%' . $filter . '%')
            ->paginate(10);
    }

    /**
    * Display a listing of the shop branches by one shop.
    */
    public function getStatusByOrder($status)
    {
        return OrderStatus::whereHas('order', function ($q) use ($status) {
            $q->where('status', $status);
        })->paginate(10);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'created_by' => 'required',
            'status' => 'required|in:pending,preparing,pickUp,onRoute,delivered,cancelled',
            'order_id' => 'required|exists:App\Models\order,id',
        ]);


        $orderStatus = OrderStatus::create($validatedData);
        return response()->json($orderStatus, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrderStatus  $orderStatus
     * @return \Illuminate\Http\Response
     */
    public function show($orderId)
    {
        return response()->json(OrderStatus::with('order')->where('order_id', $orderId)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OrderStatus  $orderStatus
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $orderId)
    {
        $orderStatus = OrderStatus::with('order')->where('order_id', $orderId)->firstOrFail();

        $validatedData = $request->validate([
            'created_by' => 'required',
            'status' => 'required|in:pending,preparing,pickUp,onRoute,delivered,cancelled',
            'order_id' => 'required|exists:App\Models\order,id',
            Rule::unique('order_status')->ignore($orderStatus->id),
        ]);

        $orderStatus->update($validatedData);
        return response()->json($orderStatus, 200);
    }
}
