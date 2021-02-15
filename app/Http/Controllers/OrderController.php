<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Order::paginate(10);
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
            'slug' => 'required|unique:orders',
            'phone_number' => 'required|unique:orders',
            'special_instruction' => 'required|string:orders',
            'order_type' => 'required|in:restaurant,shop',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|package,delivery',
            'rating_status' => 'required|one,two,three,four,five'
        ]);


        $order = Order::create($validatedData);
        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();
        return response()->json(['message' => 'Success.'], 200);
    }
}
