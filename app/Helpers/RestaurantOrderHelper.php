<?php

namespace App\Helpers;

use App\Exceptions\BadRequestException;
use App\Models\Menu;
use App\Models\MenuTopping;
use App\Models\MenuVariationValue;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderContact;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantOrderStatus;
use App\Models\Setting;
use App\Models\Township;
use Illuminate\Support\Facades\Validator;

trait RestaurantOrderHelper
{
    public static function validateOrder($request, $customerSlug = false)
    {
        $rules = [
            'slug' => 'required|unique:restaurant_orders',
            'order_date' => 'required|date_format:Y-m-d',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay',
            'delivery_mode' => 'required|in:pickup,delivery',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'promo_code' => 'nullable|string|exists:App\Models\Promocode,code',
            'promo_code_slug' => 'nullable|string|exists:App\Models\Promocode,slug',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|numeric',
            'address.street_name' => 'required|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'address.township' => 'required',
            'address.township.slug' => 'required|exists:App\Models\Township,slug',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|exists:App\Models\Menu,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variation_value_slugs' => 'nullable|array',
            'order_items.*.topping_ slugs' => 'nullable|array',
            'order_items.*.variation_value_slugs.*' => 'required|exists:App\Models\MenuVariationValue,slug',
            'order_items.*.topping_slugs.*.slug' => 'required|exists:App\Models\MenuTopping,slug',
            'order_items.*.topping_slugs.*.value' => 'required|integer',
        ];

        if ($customerSlug) {
            $rules['customer_slug'] = 'required|string|exists:App\Models\Customer,slug';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            throw new BadRequestException($validator->errors()->first(), 400);
        }
        return $validator->validated();
    }

    public static function prepareRestaurantVariations($validatedData)
    {
        $orderItems = [];
        $subTotal = 0;
        $tax = 0;

        foreach ($validatedData['order_items'] as $key => $value) {
            $menu = self::getMenu($value['slug']);
            $variations = collect(self::prepareVariations($value['variation_value_slugs']));
            $toppings = collect(self::prepareToppings($value['topping_slugs']));
            $amount = $menu->price + $variations->sum('price') + $toppings->sum('price');

            $subTotal += ($amount - $menu->discount) * $value['quantity'];

            $tax += ($amount - $menu->discount) * $menu->tax * 0.01 * $value['quantity'];
            $menu['amount'] = $amount;
            $menu['variations'] = $variations;
            $menu['quantity'] = $value['quantity'];
            $menu['variations'] = $variations;
            $menu['toppings'] = $toppings;
            $menu['tax'] = ($amount - $menu->discount) * $menu->tax * 0.01;
            array_push($orderItems, $menu->toArray());
        }

        $validatedData['product_id'] = $menu['id'];
        $validatedData['order_items'] = $orderItems;
        $validatedData['subTotal'] = $subTotal;
        $validatedData['tax'] = $tax;

        //prepare restuarantbranch info
        $restaurantBranch = self::getRestaurantBranch($validatedData['restaurant_branch_slug']);

        $validatedData['restaurant_branch_info'] = $restaurantBranch;
        $validatedData['restaurant_id'] = $restaurantBranch->restaurant->id;
        $validatedData['restaurant_branch_id'] = $restaurantBranch->id;

        // Log::debug('validatedData => ' . json_encode($validatedData));
        return $validatedData;
    }

    public static function checkVariationsExist($menus)
    {
        foreach ($menus as $key => $value) {
            $variationsCount = Menu::where('slug', $value['slug'])->first()->menuVariations()->count();

            if ($variationsCount > 0 && empty($value['variation_value_slugs'])) {
                throw new BadRequestException('The order_items.' . $key . '.variation_value_slugs is required.', 400);
            }
        }
    }

    public static function getRestaurantBranch($slug)
    {
        return RestaurantBranch::with('restaurant')->where('slug', $slug)->first();
    }

    public static function getTax()
    {
        return Setting::where('key', 'tax')->first()->value;
    }

    public static function createOrderStatus($orderId, $status = 'pending')
    {
        RestaurantOrder::where('id', $orderId)->update(['order_status' => $status]);

        RestaurantOrderStatus::create([
            'status' => $status,
            'restaurant_order_id' => $orderId,
        ]);
    }

    public static function createOrderContact($orderId, $customerInfo, $address)
    {
        $customerInfo = array_merge($customerInfo, $address);
        $customerInfo['restaurant_order_id'] = $orderId;
        $customerInfo['township_id'] = self::getTownshipId($customerInfo['township']['slug']);
        RestaurantOrderContact::create($customerInfo);
    }

    public static function createOrderItems($orderId, $orderItems)
    {
        foreach ($orderItems as $item) {
            $menu = self::getMenu($item['slug']);

            $item['menu_name'] = $menu->name;

            $item['restaurant_order_id'] = $orderId;
            $item['menu_id'] = $menu->id;
            $item['restaurant_id'] = $menu->restaurant_id;

            RestaurantOrderItem::create($item);
        }
    }

    private static function prepareVariations($variationValueSlugs)
    {
        $variations = [];

        foreach ($variationValueSlugs as $variationValueSlug) {
            $variationValue = self::getMenuVariationValue($variationValueSlug);

            $variation = [
                'name' => $variationValue->menuVariation->name,
                'value' => $variationValue->value,
                'price' => (int) $variationValue->price,
            ];

            array_push($variations, $variation);
        }

        return $variations;
    }

    private static function prepareToppings($toppingSlugs)
    {
        $toppings = [];

        foreach ($toppingSlugs as $toppingSlug) {
            $menuTopping = self::getMenuTopping($toppingSlug['slug']);

            $topping = [
                'name' => $menuTopping->name,
                'value' => $toppingSlug['value'],
                'price' => $menuTopping->price * $toppingSlug['value'],
            ];

            array_push($toppings, $topping);
        }

        return $toppings;
    }

    private static function getTownshipId($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    private static function getMenu($slug)
    {
        return Menu::where('slug', $slug)->first();
    }

    private static function getMenuVariationValue($slug)
    {
        return MenuVariationValue::with('menuVariation')->where('slug', $slug)->first();
    }

    private static function getMenuTopping($slug)
    {
        return MenuTopping::where('slug', $slug)->first();
    }

    public static function getBranches($request)
    {
        $query = RestaurantBranch::with('restaurant');
        return self::getBranchQuery($query, $request);
    }

    public static function getBranchQuery($query, $request)
    {
        $radius = CacheHelper::getRestaurantSearchRadius();

        return $query->with('restaurant')
            ->with('restaurant.availableTags')
            ->selectRaw('id, slug, name, address, contact_number, opening_time, closing_time, is_enable, restaurant_id, township_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->whereHas('restaurant', function ($q) {
                $q->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->having('distance', '<', $radius);
    }
}
