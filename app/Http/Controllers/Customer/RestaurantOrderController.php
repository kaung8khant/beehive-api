<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\NotificationHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestaurantOrderController extends Controller
{
    use NotificationHelper, PromocodeHelper, ResponseHelper, OrderHelper, StringHelper;

    public function index(Request $request)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $restaurantOrders = RestaurantOrder::with('RestaurantOrderContact')
            ->with('restaurantOrderContact.township')
            ->with('RestaurantOrderItems')
            ->where('customer_id', $customerId)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantOrders, 200);
    }

    public function show($slug)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $order = RestaurantOrder::with('RestaurantOrderContact')
            ->with('restaurantOrderContact.township')
            ->with('RestaurantOrderItems')
            ->where('slug', $slug)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        return $this->generateResponse($order, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validator = OrderHelper::validateOrder($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = Auth::guard('customers')->user()->id;

        $restaurantBranch = OrderHelper::getRestaurantBranch($validatedData['restaurant_branch_slug']);

        $validatedData['restaurant_branch_info'] = $restaurantBranch;
        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;
        $validatedData['promocode_id'] = null;

        if ($validatedData['promo_code_slug']) {
            $isPromoValid = $this->validatePromo($validatedData['promo_code_slug'], $validatedData['customer_id']);
            if (!$isPromoValid) {
                return $this->generateResponse('Invalid promo code.', 406, true);
            }

            $validatedData['promocode_id'] = Promocode::where('slug', $validatedData['promo_code_slug'])->first()->id;
        }

        $order = RestaurantOrder::create($validatedData);
        $orderId = $order->id;

        $this->createOrderStatus($orderId);
        OrderHelper::createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
        OrderHelper::createOrderItems($orderId, $validatedData['order_items'], $validatedData['promocode_id']);

        $this->notify($validatedData['restaurant_branch_slug'], ['title' => 'New Order', 'body' => "You've just recevied new order. Check now!"]);

        return $this->generateResponse(
            $order->refresh()->load('restaurantOrderContact', 'restaurantOrderContact.township', 'restaurantOrderItems'),
            201,
        );
    }

    public function destroy($slug)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $order = RestaurantOrder::where('customer_id', $customerId)->where('slug', $slug)->firstOrFail();

        if ($order->order_status === 'delivered' || $order->order_status === 'cancelled') {
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, true);
        }

        $this->createOrderStatus($order->id, 'cancelled');
        return $this->generateResponse('The order has successfully been cancelled.', 200, true);
    }

    private function notify($slug, $data)
    {
        $this->notifyRestaurant(
            $slug,
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'img' => '',
                'data' => [
                    'action' => '',
                    'type' => 'notification',
                ],
            ]
        );
    }
}
