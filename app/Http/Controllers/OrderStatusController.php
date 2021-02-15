<?php

namespace App\Http\Controllers;

use App\Models\OrderStatus;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return OrderStatus::with('order')
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
        $request['slug'] = $this->generateUniqueSlug();


        $validatedData = $request->validate([
            'created_date' => 'required|date_format:Y-m-d',
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
    public function show($slug)
    {
        return response()->json(OrderStatus::with('order')->where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OrderStatus  $orderStatus
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $orderStatus = OrderStatus::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'created_date' => 'required|date_format:Y-m-d',
            'created_by' => 'required',
            'status' => 'required|in:pending,preparing,pickUp,onRoute,delivered,cancelled',
            'order_id' => 'required|exists:App\Models\order,id',
        ]);

        $orderStatus->update($validatedData);
        return response()->json($orderStatus, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrderStatus  $orderStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        OrderStatus::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message', 'successfully deleted'], 200);
    }
}
