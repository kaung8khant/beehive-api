<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\StringHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\NotificationHelper;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatus;
use App\Models\RestaurantOrderContact;
use App\Models\RestaurantOrderItem;
use App\Models\Menu;
use App\Models\MenuTopping;
use App\Models\Township;
use App\Models\MenuVariationValue;

class RestaurantOrderController extends Controller
{
    use StringHelper, ResponseHelper, NotificationHelper;

    public function index(Request $request)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $restaurantOrders = RestaurantOrder::with('restaurant')
            ->with('restaurantBranch')
            ->with('RestaurantOrderContact')
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
        $order = RestaurantOrder::with('restaurant')
            ->with('restaurantBranch')
            ->with('RestaurantOrderContact')
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

        $validator = $this->validateOrder($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $validatedData['customer_id'] = Auth::guard('customers')->user()->id;

        $restaurantBranch = $this->getRestaurantBranch($validatedData['restaurant_branch_slug']);

        $validatedData['restaurant_branch_info'] = $restaurantBranch->toArray();
        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;

        $order = RestaurantOrder::create($validatedData);
        $orderId = $order->id;

        $this->createOrderStatus($orderId);
        $this->createOrderContact($orderId, $validatedData['customer_info'], $validatedData['address']);
        $this->createOrderItems($orderId, $validatedData['order_items']);

        // $this->notify($validatedData['restaurant_branch_slug'], ['title' => 'New Order', 'body' => "You've just recevied new order. Check now!"]);

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
            return $this->generateResponse('The order has already been ' . $order->order_status . '.', 406, TRUE);
        }

        $this->createOrderStatus($order->id, 'cancelled');
        return $this->generateResponse('The order has successfully been cancelled.', 200, TRUE);
    }

    private function validateOrder($request)
    {
        return Validator::make($request->all(), [
            'slug' => 'required|unique:orders',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:package,delivery',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'promo_code_slug' => 'nullable|string|exists:App\Models\Promocode,slug',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'required|string',
            'address.floor' => 'nullable|string',
            'address.street_name' => 'required|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'address.township' => 'required',
            'address.township.slug' => 'required|exists:App\Models\Township,slug',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|exists:App\Models\Menu,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variation_value_slugs' => 'nullable|array',
            'order_items.*.topping_slugs' => 'nullable|array',

            'order_items.*.variation_value_slugs.*' => 'required|exists:App\Models\MenuVariationValue,slug',
            'order_items.*.topping_slugs.*.slug' => 'required|exists:App\Models\MenuTopping,slug',
            'order_items.*.topping_slugs.*.value' => 'required',
        ]);
    }

    private function createOrderStatus($orderId, $status = 'pending')
    {
        RestaurantOrderStatus::create([
            'status' => $status,
            'restaurant_order_id' => $orderId,
        ]);
    }

    private function createOrderContact($orderId, $customerInfo, $address)
    {
        $customerInfo = array_merge($customerInfo, $address);
        $customerInfo['restaurant_order_id'] = $orderId;
        $customerInfo['township_id'] = $this->getTownshipId($customerInfo['township']['slug']);
        RestaurantOrderContact::create($customerInfo);
    }

    private function createOrderItems($orderId, $orderItems)
    {
        foreach ($orderItems as $item) {
            $menu = $this->getMenu($item['slug']);

            $variations = collect($this->prepareVariations($item['variation_value_slugs']));
            $toppings = collect($this->prepareToppings($item['topping_slugs']));

            $item['menu_name'] = $menu->name;
            $item['amount'] = $menu->price + $variations->sum('price') + $toppings->sum('price');
            $item['discount'] = $item['amount'] * 5 / 100;
            $item['tax'] = ($item['amount'] - $item['discount']) * 5 / 100;
            $item['restaurant_order_id'] = $orderId;
            $item['menu_id'] = $menu->id;
            $item['variations'] = $variations;
            $item['toppings'] = $toppings;

            RestaurantOrderItem::create($item);
        }
    }

    private function prepareVariations($variationValueSlugs)
    {
        $variations = [];

        foreach ($variationValueSlugs as $variationValueSlug) {
            $variationValue = $this->getMenuVariationValue($variationValueSlug);

            $variation = [
                'name' => $variationValue->menuVariation->name,
                'value' => $variationValue->value,
                'price' => $variationValue->price,
            ];

            array_push($variations, $variation);
        }

        return $variations;
    }

    private function prepareToppings($toppingSlugs)
    {
        $toppings = [];

        foreach ($toppingSlugs as $toppingSlug) {
            $menuTopping = $this->getMenuTopping($toppingSlug['slug']);

            $topping = [
                'name' => $menuTopping->name,
                'value' => $toppingSlug['value'],
                'price' => $menuTopping->price,
            ];

            if (!is_bool($toppingSlug['value']) && is_int($toppingSlug['value'])) {
                $topping['price'] = $menuTopping->price * $toppingSlug['value'];
            }

            array_push($toppings, $topping);
        }

        return $toppings;
    }

    private function getRestaurantBranch($slug)
    {
        return RestaurantBranch::with('restaurant')->where('slug', $slug)->first();
    }

    private function getMenu($slug)
    {
        return Menu::where('slug', $slug)->first();
    }

    private function getTownshipId($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    private function getMenuVariationValue($slug)
    {
        return MenuVariationValue::with('menuVariation')->where('slug', $slug)->first();
    }

    private function getMenuTopping($slug)
    {
        return MenuTopping::where('slug', $slug)->first();
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
                    'type' => 'notification'
                ]
            ]
        );
    }
}
