<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Rating::with('order', 'customer')->paginate(10);
    }

    /**
     * Display order list depending on receiver_type
     */
    public function getReceiverTypeByOrder($receiverType)
    {
        return Rating::whereHas('order', function ($q) use ($receiverType) {
            $q->where('receiver_type', $receiverType);
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
        $validatedData = $request->validate($this->getParamsToValidate());

        $validatedData['customer_id'] = $this->getCustomerId($request->customer_slug);
        $validatedData['order_id'] = $this->getOrderId($request->order_slug);

        $rating = Rating::create($validatedData);
        return response()->json($rating, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rating  $rating
     * @return \Illuminate\Http\Response
     */
    public function show($orderSlug)
    {
        return response()->json(Rating::with('order', 'customer')->where('order_id', $orderSlug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Rating  $rating
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rating = Rating::where('id', $id)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $validatedData['customer_id'] = $this->getCustomerId($request->customer_slug);
        $validatedData['order_id'] = $this->getOrderId($request->order_slug);

        $rating->update($validatedData);
        return response()->json($rating, 200);
    }

    private function getParamsToValidate()
    {
        $params = [
            'receiver_id' => 'required',
            'receiver_type' => 'required|in:restaurant,shop,biker',
            'rating' => 'required',
            'review' => 'required',
            'order_slug' => 'required|exists:App\Models\Order,slug',
            'customer_slug' => 'required|exists:App\Models\Customer,slug',
        ];

        return $params;
    }

    private function getCustomerId($slug)
    {
        return Customer::where('slug', $slug)->first()->id;
    }

    private function getOrderId($slug)
    {
        return Order::where('slug', $slug)->first()->id;
    }
}
