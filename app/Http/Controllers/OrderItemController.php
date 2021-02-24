<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\OrderItem;

class OrderItemController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return OrderItem::where('order_id', 'LIKE', '%' . $request->filter . '%')
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $orderItem = OrderItem::create($request->validate([
            'item_id' => 'required',
            'item_name' => 'required',
            'item_type' => 'required|in:product,menu',
            'amount' => 'required',
            'quantity' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'is_deleted' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
        ]));

        return response()->json($orderItem, 201);
    }

    public function show($orderId)
    {
        return response()->json(OrderItem::where('order_id', $orderId)->firstOrFail(), 200);
    }

    public function update(Request $request, $orderId)
    {
        $orderItem = OrderItem::where('order_id', $orderId)->firstOrFail();

        $orderItem->update($request->validate([
            'item_id' => 'required',
            'item_name' => 'required',
            'item_type' => 'required|in:product,menu',
            'amount' => 'required',
            'quantity' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'is_deleted' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
            Rule::unique('order_items')->ignore($orderItem->id),
        ]));

        return response()->json($orderItem, 200);
    }

    public function destroy($id)
    {
        $orderItem = OrderItem::where('id', $id)->firstOrFail();

        $orderItem->isDeleted = true;
        $orderItem->save();

        return response()->json(['message' => 'Successfully Deleted.'], 200);
    }
}
