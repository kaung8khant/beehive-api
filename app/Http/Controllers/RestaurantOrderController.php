<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\StringHelper;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use Illuminate\Http\Request;

class RestaurantOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, StringHelper;

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
            ->with('RestaurantOrderItems')
            ->whereHas('restaurantOrderContact', function ($q) use ($request) {
                $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', $request->filter);
            })
            ->orWhere('slug', $request->filter)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function getBranchOrders(Request $request, $slug)
    {
        $branchId = OrderHelper::getRestaurantBranch($slug)->id;

        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
            ->with('RestaurantOrderItems')
            ->where('restaurant_branch_id', $branchId)
            ->where(function ($query) use ($request) {
                return $query->whereHas('restaurantOrderContact', function ($q) use ($request) {
                    $q->where('customer_name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', $request->filter);
                })
                    ->orWhere('slug', $request->filter);
            })
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
     *               @OA\Property(property="customer_info", ref="#/components/schemas/RestaurantOrderContact"),
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

        $validator = OrderHelper::validateOrder($request, true);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();

        $checkVariations = OrderHelper::checkVariationsExist($validatedData['order_items']);
        if ($checkVariations) {
            return $this->generateResponse($checkVariations, 422, true);
        }

        $validatedData['customer_id'] = $this->getCustomerId($validatedData['customer_slug']);

        $restaurantBranch = OrderHelper::getRestaurantBranch($validatedData['restaurant_branch_slug']);

        $validatedData['restaurant_branch_info'] = $restaurantBranch;
        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;
        $validatedData['promocode_id'] = null;

        if ($validatedData['promo_code_slug']) {
            $isPromoValid = $this->validatePromo($validatedData['promo_code_slug'], $validatedData['customer_id'], 'restaurant');
            if (!$isPromoValid) {
                return $this->generateResponse('Invalid promo code.', 406, true);
            }

            $validatedData['promocode_id'] = Promocode::where('slug', $validatedData['promo_code_slug'])->first()->id;
        }

        $order = RestaurantOrder::create($validatedData);
        $orderId = $order->id;

        OrderHelper::createOrderStatus($orderId);
        OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
        OrderHelper::createOrderItems($orderId, $validatedData['order_items'], $validatedData['promocode_id']);

        $this->notify([
            'title' => 'Restaurant order updated',
            'body' => 'Restaurant order just has been updated',
            'status' => $request->status,
            'restaurantOrder' => RestaurantOrder::with('RestaurantOrderContact')
                ->with('restaurantOrderContact.township')
                ->with('RestaurantOrderItems')
                ->where('slug', $order->slug)
                ->firstOrFail(),
            'action' => 'create',
            'slug' => $order->slug,
        ]);

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

        OrderHelper::createOrderStatus($order->id, 'cancelled');
        return $this->generateResponse('The order has successfully been cancelled.', 200, true);
    }

    public function changeStatus(Request $request, $slug)
    {
        $order = RestaurantOrder::where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        OrderHelper::createOrderStatus($order->id, $request->status);

        $this->notify([
            'title' => 'Restaurant order updated',
            'body' => 'Restaurant order just has been updated',
            'status' => $request->status,
            'slug' => $slug,
            'action' => 'update',
        ]);

        return $this->generateResponse('The order has successfully been ' . $request->status . '.', 200, true);
    }

    private function getCustomerId($slug)
    {
        return Customer::where('slug', $slug)->first()->id;
    }

    private function notify($data)
    {
        $this->notifyAdmin(
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'data' => [
                    'action' => $data['action'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );
        $this->notifyRestaurant(
            $data['slug'],
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'data' => [
                    'action' => $data['action'],
                    'type' => 'restaurantOrder',
                    'status' => !empty($data['status']) ? $data['status'] : "",
                    'restaurantOrder' => !empty($data['restaurantOrder']) ? $data['restaurantOrder'] : "",
                    'slug' => !empty($data['slug']) ? $data['slug'] : "",
                ],
            ]
        );
    }
}
