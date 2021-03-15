<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\StringHelper;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderContact;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Menu;

class OrderController extends Controller
{
    use StringHelper;

     /**
     * @OA\Get(
     *      path="/api/v2/admin/orders",
     *      operationId="getOrderLists",
     *      tags={"Orders"},
     *      summary="Get list of orders",
     *      description="Returns list of orders",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
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
    public function index(Request $request)
    {
        return Order::with('orderContact')
            ->whereDate('order_date', '>=', $request->from)
            ->whereDate('order_date', '<=', $request->to)
            ->where('order_type', $request->type)
            ->whereHas('orderContact', function ($q) use ($request) {
                $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', $request->filter);
            })->orWhere('slug', $request->filter)
            ->orderBy('id', 'desc')
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/orders",
     *      operationId="storeOrders",
     *      tags={"Orders"},
     *      summary="Create a order",
     *      description="Returns newly created order",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created order",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(
     *               @OA\Property(property="customer_slug", type="string", example="D16AAF"),
     *               @OA\Property(property="order_date", type="string", example="2021-02-19"),
     *               @OA\Property(property="order_type", type="string", example="shop"),
     *               @OA\Property(property="special_instruction", type="string", example="special_instruction"),
     *               @OA\Property(property="payment_mode", type="string", example="CBPay"),
     *               @OA\Property(property="delivery_mode", type="string", example="delivery"),
     *               @OA\Property(property="rating", type="string", example="1"),
     *               @OA\Property(property="customer_info", ref="#/components/schemas/OrderContact"),
     *               @OA\Property(property="order_items", type="array",
     *               @OA\Items(type="object",
     *                  @OA\Property(property="value", type="string", example="value"),
     *                  @OA\Property(property="item_slug", type="string", example="D16AAF"),
     *                  @OA\Property(property="item_name", type="string",example=0.00),
     *                  @OA\Property(property="item_type", type="string",example="product"),
     *                  @OA\Property(property="quantity", type="number",example=0.00),
     *                  @OA\Property(property="amount", type="number",example=0.00),
     *                  @OA\Property(property="tax", type="number",example=0.00),
     *                  @OA\Property(property="discount", type="number",example=0.00),
     *                  ),
     *                  ),
     *
     *              )
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
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $this->validateOrder($request);
        $validatedData['customer_id'] = $this->getCustomerId($validatedData['customer_slug']);

        $order = Order::create($validatedData);
        $orderId = $order->id;

        $this->createOrderStatus($orderId);
        $this->createOrderContact($orderId, $validatedData['customer_info']);
        $this->createOrderItems($orderId, $validatedData['order_items'], $request->order_type);

        return response()->json($order->refresh()->load('orderContact', 'orderItems'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/orders/{slug}",
     *      operationId="Order",
     *      tags={"Orders"},
     *      summary="Get One Order",
     *      description="Returns a requested Order",
     *      @OA\Parameter(
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
    public function show($slug)
    {
        $order = Order::with('orderContact')
            ->with('orderItems')
            ->where('slug', $slug)
            ->firstOrFail();
        return response()->json($order, 200);
    }

    public function update(Request $request, $slug)
    {
        // $order = Order::where('slug', $slug)->firstOrFail();

        // $validatedData = $request->validate([
        //     'special_instruction' => 'nullable',
        //     'order_date' => 'required|date_format:Y-m-d',
        //     'order_type' => 'required|in:restaurant,shop',
        //     'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
        //     'delivery_mode' => 'required|in:package,delivery',
        //     'rating' => 'required|in:1,2,3,4,5',
        // ]);

        // $order->update($validatedData);
        // return response()->json($order, 200);
    }


    /**
     * @OA\Delete(
     *      path="/api/v2/admin/orders/{slug}",
     *      operationId="deleteOrder",
     *      tags={"Orders"},
     *      summary="Delete One Order",
     *      description="Delete one specific order",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested order ",
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
    public function destroy($slug)
    {
        $order = Order::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return response()->json(['message' => 'The order has already been ' . $order->order_status . '.'], 406);
        }

        $this->createOrderStatus($order->id, 'cancelled');
        return response()->json(['message' => 'Successfully cancelled.'], 200);
    }

    private function validateOrder(Request $request)
    {
        $rules = [
            'slug' => 'required|unique:orders',
            'customer_slug' => 'required|exists:App\Models\Customer,slug',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'order_type' => 'required|in:restaurant,shop',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'customer_info.house_number' => 'required|string',
            'customer_info.floor' => 'nullable|string',
            'customer_info.street_name' => 'required|string',
            'customer_info.latitude' => 'nullable|numeric',
            'customer_info.longitude' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.item_slug' => '',
            'order_items.*.item_name' => 'required|string',
            'order_items.*.item_type' => 'required|in:product,menu',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.amount' => 'required|numeric',
            'order_items.*.tax' => 'required|numeric',
            'order_items.*.discount' => 'required|numeric',
        ];

        if ($request->order_type === 'restaurant') {
            $rules['order_items.*.item_slug'] = 'required|exists:App\Models\Menu,slug';
        } else {
            $rules['order_items.*.item_slug'] = 'required|exists:App\Models\Product,slug';
        }

        return $request->validate($rules);
    }

    private function createOrderStatus($orderId, $status = 'pending')
    {
        OrderStatus::create([
            'order_id' => $orderId,
            'status' => $status,
            'created_by' => Auth::guard('users')->user()->name,
        ]);
    }

    private function createOrderContact($orderId, $customerInfo)
    {
        $customerInfo['order_id'] = $orderId;
        OrderContact::create($customerInfo);
    }

    private function createOrderItems($orderId, $orderItems, $orderType)
    {
        foreach ($orderItems as $item) {
            $item['order_id'] = $orderId;

            if ($orderType === 'restaurant') {
                $item['item_id'] = $this->getMenuId($item['item_slug']);
            } else {
                $item['item_id'] = $this->getProductId($item['item_slug']);
            }

            OrderItem::create($item);
        }
    }

    private function getCustomerId($slug)
    {
        return Customer::where('slug', $slug)->first()->id;
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/customers/{slug}/orders",
     *      operationId="getOrdersByCustomer",
     *      tags={"Orders"},
     *      summary="Get Orders By Customer",
     *      description="Returns list of orders",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested orders",
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
    public function getOrdersByCustomer($slug)
    {
        $customerId = $this->getCustomerId($slug);
        return Order::where('customer_id', $customerId)->orderBy('id', 'desc')->paginate(10);
    }
}
