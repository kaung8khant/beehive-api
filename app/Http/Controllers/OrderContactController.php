<?php

namespace App\Http\Controllers;

use App\Models\OrderContact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;

class OrderContactController extends Controller
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

        return OrderContact::where('customerName', 'LIKE', '%' . $filter . '%')
        ->where('phoneNumber', 'LIKE', '%' . $filter . '%')
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
        $orderContact = OrderContact::create($request->validate([
            'customerId' => 'required|unique:order_contacts',
            'customerName' => 'required',
            'phoneNumber' => 'required',
            'houseNumber' => 'required',
            'floor' => 'required',
            'streetName' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
        ]));

        return response()->json($orderContact, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrderContact  $orderContact
     * @return \Illuminate\Http\Response
     */
    public function show($orderId)
    {
        return response()->json(OrderContact::where('order_id', $orderId)->firstOrFail(), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OrderContact  $orderContact
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderContact $orderContact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OrderContact  $orderContact
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $orderId)
    {
        $orderContact = OrderContact::where('order_id', $orderId)->firstOrFail();

        $orderContact->update($request->validate([
            'customerId'=>'required|unique:order_contacts',
            'customerName'=>'required',
            'phoneNumber'=>'required',
            'houseNumber'=>'required',
            'floor' => 'required',
            'streetName' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'order_id' => 'required|exists:App\Models\Order,id',
            Rule::unique('order_contacts')->ignore($orderContact->id),
        ]));

        return response()->json($orderContact, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrderContact  $orderContact
     * @return \Illuminate\Http\Response
     */
    public function destroy($orderId)
    {
        OrderContact::where('order_id', $orderId)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
