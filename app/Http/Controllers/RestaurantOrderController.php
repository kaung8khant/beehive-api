<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderContact;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantOrderController extends Controller
{
    use StringHelper, ResponseHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-orders",
     *      operationId="getRestaurantOrderLists",
     *      tags={"Restaurant Orders"},
     *      summary="Get list of restaurant orders",
     *      description="Returns list of restaurant orders",
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
        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact')
            ->with('restaurantOrderContact.township')
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function getBranchOrders(Request $request, $slug)
    {
        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
        // ->whereDate('order_date', '>=', $request->from)
        // ->whereDate('order_date', '<=', $request->to)
            ->whereHas('restaurantBranch', function ($q) use ($slug) {
                $q->where('slug', $slug);
            })
            ->whereHas('restaurantOrderContact', function ($q) use ($request) {
                $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', $request->filter);
            })->orWhere('slug', $request->filter)
            ->latest()
            ->paginate(10)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-orders/{slug}",
     *      operationId="getOneRestaurantOrder",
     *      tags={"Restaurant Orders"},
     *      summary="Get One Restaurant Order",
     *      description="Returns a requested restaurant order",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant Order",
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
    // public function show($slug)
    // {
    //     // $customer_id = Auth::guard('customers')->user()->id;
    //     $order = RestaurantOrder::with('RestaurantOrderContact')
    //         ->with('RestaurantOrderItems')
    //         ->where('slug', $slug)
    //         // ->where('customer_id', $customer_id)
    //         ->firstOrFail();

    //     return $this->generateResponse($order, 200);
    // }

    public function show($slug)
    {
        $order = RestaurantOrder::with('RestaurantOrderContact')
            ->with('restaurantOrderContact.township')
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->generateResponse($order, 200);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/restaurant-orders",
     *      operationId="storeRestaurantOrder",
     *      tags={"Restaurant Orders"},
     *      summary="Create Restaurant Order",
     *      description="Returns newly created order",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created order",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(
     *               @OA\Property(property="customer_slug", type="string", example=""),
     *               @OA\Property(property="restaurant_branch_slug", type="string", example=""),
     *               @OA\Property(property="order_date", type="string", example="2021-02-19"),
     *               @OA\Property(property="special_instruction", type="string", example=""),
     *               @OA\Property(property="payment_mode", type="string", example="COD"),
     *               @OA\Property(property="delivery_mode", type="string", example="delivery"),
     *               @OA\Property(property="customer_info", ref="#/components/schemas/OrderContact"),
     *               @OA\Property(property="order_items", type="array",
     *               @OA\Items(type="object",
     *                  @OA\Property(property="menu_slug", type="string", example=""),
     *                  @OA\Property(property="menu_name", type="string",example=""),
     *                  @OA\Property(property="amount", type="number",example=0.00),
     *                  @OA\Property(property="quantity", type="number",example=0.00),
     *                  @OA\Property(property="tax", type="number",example=0.00),
     *                  @OA\Property(property="discount", type="number",example=0.00),
     *                  @OA\Property(property="variations", type="array",
     *                  @OA\Items(type="object",
     *                  @OA\Property(property="name", type="string", example=""),
     *                  @OA\Property(property="value", type="number",example=0.00),
     *                      ),
     *                      ),
     *                  @OA\Property(property="toppings", type="array",
     *                  @OA\Items(type="object",
     *                  @OA\Property(property="name", type="string", example=""),
     *                  @OA\Property(property="value", type="number",example=0.00),
     *                      ),
     *                      ),
     *                  ),
     *                  ),
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

        $validator = $this->validateOrder($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = $this->getCustomerId($request->customer_slug);

        $restaurantBranch = $this->getRestaurantBranch($validatedData['restaurant_branch_slug']);

        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;

        $order = RestaurantOrder::create($validatedData);
        $orderId = $order->id;

        $this->createOrderStatus($orderId);
        $this->createOrderContact($orderId, $validatedData['customer_info']);
        $this->createOrderItems($orderId, $validatedData['order_items']);

        return $this->generateResponse($order->refresh()->load('restaurantOrderContact', 'restaurantOrderItems'), 201);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/restaurant-orders/{slug}",
     *      operationId="deleteRestaurantOrder",
     *      tags={"Restaurant Orders"},
     *      summary="Delete One Restaurant Order",
     *      description="Delete one specific restaurant order",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant order",
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
        $order = RestaurantOrder::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $this->createOrderStatus($order->id, 'cancelled');
        return $this->generateResponse('The order has successfully been cancelled.', 200, true);
    }

    public function changeStatus(Request $request, $slug)
    {
        $order = RestaurantOrder::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $this->createOrderStatus($order->id, $request->status);
        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
    }

    private function validateOrder($request)
    {
        return Validator::make($request->all(), [
            'slug' => 'required|unique:restaurant_orders',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'customer_info.house_number' => 'required|string',
            'customer_info.floor' => 'nullable|string',
            'customer_info.street_name' => 'required|string',
            'customer_info.latitude' => 'nullable|numeric',
            'customer_info.longitude' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.menu_slug' => 'required|exists:App\Models\Menu,slug',
            'order_items.*.menu_name' => 'required|string',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.amount' => 'required|numeric',
            'order_items.*.tax' => 'required|numeric',
            'order_items.*.discount' => 'required|numeric',
            'order_items.*.variations' => 'nullable|array',
            'order_items.*.toppings' => 'nullable|array',
            'order_items.*.variations.*.name' => 'required|string',
            'order_items.*.variations.*.value' => 'required|numeric',
            'order_items.*.toppings.*.name' => 'required|string',
            'order_items.*.toppings.*.value' => 'required|numeric',
        ]);
    }

    private function createOrderStatus($orderId, $status = 'pending')
    {
        RestaurantOrderStatus::create([
            'status' => $status,
            'restaurant_order_id' => $orderId,
        ]);
    }

    private function createOrderContact($orderId, $customerInfo)
    {
        $customerInfo['restaurant_order_id'] = $orderId;
        RestaurantOrderContact::create($customerInfo);
    }

    private function createOrderItems($orderId, $orderItems)
    {
        foreach ($orderItems as $item) {
            $item['restaurant_order_id'] = $orderId;
            $item['menu_id'] = $this->getMenuId($item['menu_slug']);
            $item['variations'] = $item['variations'];
            $item['toppings'] = $item['toppings'];

            RestaurantOrderItem::create($item);
        }
    }

    private function getRestaurantBranch($slug)
    {
        return RestaurantBranch::where('slug', $slug)->first();
    }

    public function getCustomerId($slug)
    {
        return Customer::where('slug', $slug)->first()->id;
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }
}
