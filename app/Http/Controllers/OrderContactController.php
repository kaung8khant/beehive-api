<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\OrderContact;
use App\Models\Order;

class OrderContactController extends Controller
{
    use StringHelper;

     /**
     * @OA\Get(
     *      path="/api/v2/admin/orders/{slug}/contact",
     *      operationId="Order",
     *      tags={"OrderContact"},
     *      summary="Get One Order Contact",
     *      description="Returns a requested order contact",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested order",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show($slug)
    {
        return OrderContact::whereHas('order', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->firstOrFail();
    }

     /**
     * @OA\Put(
     *      path="/api/v2/admin/orders/{slug}/contact",
     *      operationId="updateOrderContact",
     *      tags={"OrderContact"},
     *      summary="Update a OrderContact",
     *      description="Update a requested OrderContact",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a Order",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New Order Contact data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/OrderContact")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function update(Request $request, $slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();
        $orderContact = $order->orderContact;

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