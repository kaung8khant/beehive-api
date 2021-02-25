<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\OrderContact;
use App\Models\Order;

class OrderContactController extends Controller
{
    use StringHelper;

    public function show($slug)
    {
        return OrderContact::whereHas('order', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->firstOrFail();
    }

    public function update(Request $request, $slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();
        $orderContact = $order->order_contact;

        $orderContact->update($request->validate([
            'customer_name' => 'required|string',
            'phone_number' => 'required|string',
            'house_number' => 'required|string',
            'floor' => 'nullable|string',
            'street_name' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]));

        return response()->json($orderContact, 200);
    }
}
