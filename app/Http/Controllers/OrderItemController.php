<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;

class OrderItemController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter= $request->filter;

        return OrderItem::where('order_id', 'LIKE', '%' . $filter . '%')
        ->paginate(10);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $orderItem = OrderItem::create($request->validate([
            'itemId' => 'required',
            'itemName' => 'required',
            'itemType' => 'required|in:product,menu',
            'amount' => 'required',
            'quantity' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'isDeleted' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
        ]));

        return response()->json($orderItem, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     */
    public function show($orderId)
    {
        return response()->json(OrderItem::where('order_id', $orderId)->firstOrFail(), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderItem $orderItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $orderId)
    {
        $orderItem = OrderItem::where('order_id', $orderId)->firstOrFail();

        $orderItem->update($request->validate([
            'itemId' => 'required',
            'itemName' => 'required',
            'itemType' => 'required|in:product,menu',
            'amount' => 'required',
            'quantity' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'isDeleted' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
            Rule::unique('order_items')->ignore($orderItem->id),
        ]));

        return response()->json($orderItem, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrderItem  $orderItem
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        OrderItem::where('id', $id)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
