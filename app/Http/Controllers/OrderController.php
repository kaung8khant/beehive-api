<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'required',
            'order_type' => 'required|in:restaurant,shop',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'rating_status' => 'required|in:one,two,three,four,five'
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
    public function show($slug)
    {
        return response()->json(Order::where('slug', $slug)->firstOrFail(), 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'special_instruction' => 'required',
            'order_date' => 'required|date_format:Y-m-d',
            'order_type' => 'required|in:restaurant,shop',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'rating_status' => 'required|in:one,two,three,four,five',
             Rule::unique('orders')->ignore($order->id),
        ]);

        $order->update($validatedData);
        return response()->json($order, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Order::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
