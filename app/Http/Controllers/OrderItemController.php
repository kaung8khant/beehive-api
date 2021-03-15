<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Menu;
use App\Models\Product;

class OrderItemController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/admin/orders/{slug}/items",
     *      operationId="getOrderItemLists",
     *      tags={"OrderItems"},
     *      summary="Get list of order items",
     *      description="Returns list of order items",
     *    @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested Order",
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
    public function index($slug)
    {
        $orderItem = OrderItem::whereHas('order', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->get();

        return $orderItem;
    }


      /**
     * @OA\Post(
     *      path="/api/v2/admin/orders/{slug}/items",
     *      operationId="storeOrderItems",
     *      tags={"OrderItems"},
     *      summary="Create a order item",
     *      description="Returns newly created order item",
     *      @OA\Parameter(
     *      name="slug",
     *      description="Slug of a requested Order",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *          type="string"
     *       )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created order item object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/OrderItem")
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
    public function store(Request $request, $slug)
    {
        $order = $this->getOrder($slug);

        $validatedData = $this->validateOrderItem($request, $order->order_type);
        $validatedData['order_id'] = $order->id;

        if ($order->order_type === 'restaurant') {
            $validatedData['item_id'] = $this->getMenuId($validatedData['item_slug']);
        } else {
            $validatedData['item_id'] = $this->getProductId($validatedData['item_slug']);
        }

        try {
            $orderItem = $this->getOrderItem($order->id, $validatedData['item_id']);

            if ($orderItem->is_deleted) {
                $orderItem->is_deleted = false;
                $orderItem->save();
                return response()->json($orderItem, 201);
            }

            return response()->json(['message' => 'The item has already been added for this order. Increase the quantity instead.'], 409);
        } catch (\Exception $e) {
            $orderItem = OrderItem::create($validatedData);
            return response()->json($orderItem, 201);
        }
    }


    /**
     * @OA\Get(
     *      path="/api/v2/admin/orders/{slug}/items/{id}",
     *      operationId="getOneOrderItem",
     *      tags={"OrderItems"},
     *      summary="Get One order item",
     *      description="Returns a requested orders items",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested Order",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="id of a requested Order item",
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
    public function show($slug, $itemId)
    {
        $order = $this->getOrder($slug);
        $orderItem = $this->getOrderItem($order->id, $itemId);
        return response()->json($orderItem, 200);
    }


    /**
     * @OA\Put(
     *      path="/api/v2/admin/orders/{slug}/items/{id}",
     *      operationId="updateOrderItem",
     *      tags={"OrderItems"},
     *      summary="Update a order item",
     *      description="Update a requested order item",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a order",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="id to identify a order item",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New order item data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/OrderItem")
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
    public function update(Request $request, $slug, $itemId)
    {
        $order = $this->getOrder($slug);
        $orderItem = $this->getOrderItem($order->id, $itemId);

        $orderItem->update($request->validate([
            'quantity' => 'required|integer',
            'amount' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'required|numeric',
        ]));

        return response()->json($orderItem, 200);
    }


     /**
     * @OA\Delete(
     *      path="/api/v2/admin/orders/{slug}/items/{id}",
     *      operationId="deleteOrderItem",
     *      tags={"OrderItems"},
     *      summary="Delete One Order Item",
     *      description="Delete one specific order item",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested order ",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="id of a requested order item",
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
    public function destroy($slug, $itemId)
    {
        $order = $this->getOrder($slug);
        $orderItem = $this->getOrderItem($order->id, $itemId);

        if ($orderItem->is_deleted) {
            return response()->json(['message' => 'The order item has already been deleted.'], 406);
        }

        $orderItem->is_deleted = true;
        $orderItem->save();

        return response()->json(['message' => 'Successfully Deleted.'], 200);
    }

    private function validateOrderItem(Request $request, $orderType)
    {
        $rules = [
            'item_slug' => '',
            'item_name' => 'required|string',
            'item_type' => 'required|in:product,menu',
            'quantity' => 'required|integer',
            'amount' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'required|numeric',
        ];

        if ($orderType === 'restaurant') {
            $rules['item_slug'] = 'required|exists:App\Models\Menu,slug';
        } else {
            $rules['item_slug'] = 'required|exists:App\Models\Product,slug';
        }

        return $request->validate($rules);
    }

    private function getOrder($slug)
    {
        return Order::where('slug', $slug)->firstOrFail();
    }

    private function getOrderItem($orderId, $itemId)
    {
        return OrderItem::where('order_id', $orderId)->where('item_id', $itemId)->firstOrFail();
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }
}
