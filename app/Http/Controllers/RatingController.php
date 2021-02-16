<?php

namespace App\Http\Controllers;

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
        return Rating::with('order', 'customer')
        ->paginate(10);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rating = Rating::create($request->validate([
            'receiver_id' => 'required',
            'receiver_type' => 'required|in:restaurant,shop,biker',
            'rating' => 'required',
            'review' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
            'customer_id' => 'required|exists:App\Models\Customer,id',
        ]));

        return response()->json($rating, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rating  $rating
     * @return \Illuminate\Http\Response
     */
    public function show($orderId)
    {
        return response()->json(Rating::with('order', 'customer')->where('order_id', $orderId)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Rating  $rating
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $orderId)
    {
        $rating = Rating::where('order_id', $orderId)->firstOrFail();

        $rating->update($request->validate([
            'receiver_id' => 'required',
            'receiver_type' => 'required|in:restaurant,shop,biker',
            'rating' => 'required',
            'review' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
            'customer_id' => 'required|exists:App\Models\Customer,id',
            Rule::unique('rating')->ignore($rating->id),
        ]));

        return response()->json($rating, 200);
    }
}
