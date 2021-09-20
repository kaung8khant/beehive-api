<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\CacheHelper;
use App\Helpers\GeoHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Admin\v3\RestaurantOrderController;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\MenuCart;
use App\Models\MenuCartItem;
use App\Models\MenuOption;
use App\Models\MenuOptionItem;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
use App\Models\RestaurantBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use stdClass;

class RestaurantCartController extends CartController
{
    use ResponseHelper;

    private $customer;
    private $resMes;

    public function __construct(Request $request)
    {
        if ($request->customer_slug) {
            $this->customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        }

        $this->resMes = config('response-en');
    }

    public function store(Request $request, Menu $menu)
    {
        $validator = $this->validateMenuCart($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        try {
            $restaurantBranch = RestaurantBranch::where('slug', $request->restaurant_branch_slug)->first();
            $menuCart = MenuCart::where('customer_id', $this->customer->id)->first();
            $menuData = $this->prepareMenuData($menu, $request);

            try {
                $this->checkAddressAndBranch($request->address, [$menu->id], $restaurantBranch->id);
            } catch (ForbiddenException $e) {
                return $this->generateResponse($e->getMessage(), 400, true);
            }

            if ($menuCart) {
                if ($menuCart->restaurant_branch_id !== $restaurantBranch->id) {
                    $sameBranchError = $this->resMes['restaurant_cart']['same_branch_err'];
                    return $this->generateResponse($sameBranchError, 400, true);
                }

                $menuCart->address = $request->address;
                $menuCart->save();

                $menuCartItem = $this->getMenuCartItem($menu->id, $menuCart->id, $menuData['key']);

                if ($menuCartItem) {
                    $menuData['quantity'] = $menuCartItem->menu['quantity'] + $request->quantity;
                    $menuCartItem->menu = $menuData;
                    $menuCartItem->save();
                } else {
                    $this->createMenuCartItem($menuCart->id, $menu->id, $menuData);
                }

                if ($menuCart->promocode) {
                    $request['promo_code'] = $menuCart->promocode;
                    $this->applyPromocode($request);
                }
            } else {
                $menuCart = DB::transaction(function () use ($restaurantBranch, $menu, $menuData, $request) {
                    $menuCart = $this->createMenuCart($this->customer->id, $restaurantBranch->id, $request->address);
                    $this->createMenuCartItem($menuCart->id, $menu->id, $menuData);
                    return $menuCart;
                });
            }

            $data = $this->prepareMenuCartData($menuCart->refresh()->load('menuCartItems'));
            return $this->generateResponse($data, 200);
        } catch (BadRequestException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }
    }

    private function validateMenuCart($request)
    {
        return Validator::make($request->all(), [
            'customer_slug' => 'required|exists:App\Models\Customer,slug',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'quantity' => 'required|integer',
            'variant_slug' => 'required|exists:App\Models\MenuVariant,slug',
            'toppings' => 'nullable|array',
            'toppings.*.slug' => 'required|exists:App\Models\MenuTopping,slug',
            'toppings.*.quantity' => 'required|integer',
            'option_items' => 'nullable|array',
            'option_items.*' => 'required|exists:App\Models\MenuOptionItem,slug',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|integer',
            'address.street_name' => 'nullable|string',
            'address.latitude' => 'required|numeric',
            'address.longitude' => 'required|numeric',
        ]);
    }

    private function prepareMenuData($menu, $request)
    {
        if (!isset($request->option_items)) {
            $request['option_items'] = [];
        }

        $menuVariant = $this->getVariant($menu, $request->variant_slug);
        $toppings = $this->getToppings($menu, $request->toppings);
        $optionItems = $this->getOptionItems($menu, $request->option_items);

        $amount = $menuVariant->price + collect($toppings)->sum('price') + collect($optionItems)->sum('price');
        $tax = ($amount - $menuVariant->discount) * $menuVariant->tax * 0.01;
        $discount = $menuVariant->discount;

        return [
            'key' => $this->getMenuKey($menu->slug, $menuVariant->slug, $toppings, $optionItems),
            'slug' => $menu->slug,
            'name' => $menu->name,
            'description' => $menu->description,
            'amount' => $amount,
            'tax' => $tax,
            'discount' => $discount,
            'quantity' => $request->quantity,
            'variant' => $menuVariant,
            'toppings' => $toppings,
            'options' => $optionItems,
            'images' => $menu->images,
        ];
    }

    private function getVariant($menu, $variantSlug)
    {
        $menuVariant = MenuVariant::where('menu_id', $menu->id)->where('slug', $variantSlug)->first();

        if (!$menuVariant) {
            throw new BadRequestException($this->resMes['restaurant_cart']['variant_err']);
        }

        return $menuVariant;
    }

    private function getToppings($menu, $toppings)
    {
        $menuToppings = collect($toppings)->map(function ($value, $key) use ($menu) {
            $menuTopping = MenuTopping::where('slug', $value['slug'])->first();

            if ($menuTopping->menu_id != $menu->id) {
                throw new BadRequestException(sprintf($this->resMes['restaurant_cart']['topping_err'], $menuTopping->name, $menu->name));
            }

            if ($value['quantity'] > $menuTopping->max_quantity) {
                throw new BadRequestException(sprintf($this->resMes['restaurant_cart']['topping_qty_err'], $menuTopping->name, $menu->name, $menuTopping->max_quantity));
            }

            return [
                'slug' => $menuTopping->slug,
                'quantity' => $value['quantity'],
                'price' => $menuTopping->price,
            ];
        });

        return $menuToppings->sortBy('slug')->values();
    }

    private function getOptionItems($menu, $optionItems)
    {
        $itemsCount = array_count_values($optionItems);

        foreach ($itemsCount as $itemSlug => $count) {
            if ($count > 1) {
                throw new BadRequestException(sprintf($this->resMes['restaurant_cart']['option_dup_err'], $menu->name));
            }
        }

        $menuOptions = collect($optionItems)->map(function ($value, $key) use ($menu) {
            $menuOptionItem = MenuOptionItem::where('slug', $value)->first();

            if ($menuOptionItem->menuOption->menu_id != $menu->id) {
                throw new BadRequestException(sprintf($this->resMes['restaurant_cart']['option_item_err'], $menuOptionItem->name, $menu->name));
            }

            return [
                'slug' => $menuOptionItem->slug,
                'price' => $menuOptionItem->price,
                'menu_option_id' => $menuOptionItem->menu_option_id,
            ];
        });

        foreach ($menuOptions->countBy('menu_option_id') as $menuOptionId => $count) {
            $menuOption = MenuOption::with('menu')->where('id', $menuOptionId)->first();

            if ($count > $menuOption->max_choice) {
                throw new BadRequestException(sprintf($this->resMes['restaurant_cart']['option_max_err'], $menuOption->name, $menu->name, $menuOption->max_choice));
            }
        }

        return $menuOptions->sortBy('slug')->values();
    }

    private function getMenuKey($menuSlug, $menuVariantSlug, $toppings, $optionItems)
    {
        return $menuSlug . '-' . $menuVariantSlug . '-' . implode('-', $toppings->pluck('slug')->toArray()) . '-' . implode('-', $toppings->pluck('quantity')->toArray()) . '-' . implode('-', $optionItems->pluck('slug')->toArray());
    }

    private function createMenuCart($customerId, $restaurantBranchId, $address)
    {
        return MenuCart::create([
            'slug' => StringHelper::generateUniqueSlug(),
            'customer_id' => $customerId,
            'restaurant_branch_id' => $restaurantBranchId,
            'address' => $address,
        ]);
    }

    private function createMenuCartItem($menuCartId, $menuId, $menuData)
    {
        MenuCartItem::create([
            'menu_cart_id' => $menuCartId,
            'menu_id' => $menuId,
            'menu' => $menuData,
        ]);
    }

    private function getMenuCartItem($menuId, $menuCartId, $key)
    {
        return MenuCartItem::where('menu_cart_id', $menuCartId)
            ->where('menu_id', $menuId)
            ->get()
            ->first(function ($value) use ($key) {
                return $value->menu['key'] === $key;
            });
    }

    public function updateQuantity(Request $request, Menu $menu)
    {
        $menuCart = MenuCart::where('customer_id', $this->customer->id)->first();
        if (!$menuCart) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        $menuCartItem = $this->getMenuCartItem($menu->id, $menuCart->id, $request->key);
        if (!$menuCartItem) {
            return $this->generateResponse($this->resMes['restaurant_cart']['no_item'], 400, true);
        }

        $menuData = $menuCartItem->menu;
        $menuData['quantity'] = $request->quantity;

        $menuCartItem->menu = $menuData;
        $menuCartItem->save();

        if ($menuCart->promocode) {
            $request['promo_code'] = $menuCart->promocode;
            $this->applyPromocode($request);
        }

        $data = $this->prepareMenuCartData($menuCart->refresh()->load('menuCartItems'));
        return $this->generateResponse($data, 200);
    }

    public function delete(Request $request, Menu $menu)
    {
        $menuCart = MenuCart::where('customer_id', $this->customer->id)->first();
        if (!$menuCart) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        $menuCartItem = $this->getMenuCartItem($menu->id, $menuCart->id, $request->key);
        if (!$menuCartItem) {
            return $this->generateResponse($this->resMes['restaurant_cart']['no_item'], 400, true);
        }
        $menuCartItem->delete();

        if (MenuCartItem::where('menu_cart_id', $menuCart->id)->count() === 0) {
            $menuCart->delete();
            return $this->generateResponse(new stdClass(), 200);
        }

        if ($menuCart->promocode) {
            $request['promo_code'] = $menuCart->promocode;
            $this->applyPromocode($request);
        }

        $data = $this->prepareMenuCartData($menuCart->refresh()->load('menuCartItems'));
        return $this->generateResponse($data, 200);
    }

    public function deleteCart()
    {
        $menuCart = MenuCart::where('customer_id', $this->customer->id)->first();
        if (!$menuCart) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        $menuCart->delete();
        return $this->generateResponse('success', 200, true);
    }

    public function applyPromocode(Request $request)
    {
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $this->customer->id)->first();
        if (!$menuCart) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        try {
            $request['sub_total'] = $this->getSubTotal($menuCart->menuCartItems->pluck('menu'));
            $request['order_items'] = $this->getOrderItems($menuCart->menuCartItems);

            $promoData = $this->getPromoData($request, $this->customer, 'restaurant');

            $menuCart->promocode_id = $promoData['promocode_id'];
            $menuCart->promocode = $promoData['promocode'];
            $menuCart->promo_amount = $promoData['promo_amount'];
            $menuCart->save();

            $cartData = $this->prepareMenuCartData($menuCart);
            return $this->generateResponse($cartData, 200);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 403, true);
        }
    }

    public function removePromocode()
    {
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $this->customer->id)->first();
        if (!$menuCart) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        $menuCart->promocode_id = null;
        $menuCart->promocode = null;
        $menuCart->promo_amount = 0;
        $menuCart->save();

        $cartData = $this->prepareMenuCartData($menuCart);
        return $this->generateResponse($cartData, 200);
    }

    public function checkout(Request $request)
    {
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $this->customer->id)->first();
        if (!$menuCart || !isset($menuCart->menuCartItems) || $menuCart->menuCartItems->count() === 0) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        $address = $menuCart->address;
        $branch = RestaurantBranch::with('restaurant')->where('id', $menuCart->restaurant_branch_id)->first();

        $distance = GeoHelper::calculateDistance($address['latitude'], $address['longitude'], $branch->latitude, $branch->longitude);
        $deliveryFee = $branch->free_delivery ? 0 : GeoHelper::calculateDeliveryFee($distance);

        $request['restaurant_branch_slug'] = RestaurantBranch::where('id', $menuCart->restaurant_branch_id)->value('slug');
        $request['address'] = $menuCart->address;
        $request['delivery_fee'] = $deliveryFee;

        $request['promo_code'] = $menuCart->promocode;
        $request['order_items'] = $this->getOrderItems($menuCart->menuCartItems);

        $order = new RestaurantOrderController($this->getMessageService(), $this->getPaymentService($request->payment_mode));
        $result = $order->store($request);

        if (json_decode($result->getContent(), true)['status'] === 201) {
            $menuCart->delete();
        }

        return $result;
    }

    public function updateAddress(Request $request)
    {
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $this->customer->id)->first();
        if (!$menuCart || !isset($menuCart->menuCartItems) || $menuCart->menuCartItems->count() === 0) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        try {
            $menuIds = $menuCart->menuCartItems->pluck('menu_id')->unique();
            $this->checkAddressAndBranch($request->address, $menuIds, $menuCart->restaurant_branch_id);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }

        $menuCart->address = $request->address;
        $menuCart->save();

        $data = $this->prepareMenuCartData($menuCart->refresh()->load('menuCartItems'));
        return $this->generateResponse($data, 200);
    }

    private function checkAddressAndBranch($address, $menuIds, $restaurantBranchId)
    {
        $restaurantBranch = RestaurantBranch::where('id', $restaurantBranchId)->first();
        $distance = GeoHelper::calculateDistance($address['latitude'], $address['longitude'], $restaurantBranch->latitude, $restaurantBranch->longitude);

        if ($distance > CacheHelper::getRestaurantSearchRadius()) {
            throw new ForbiddenException($this->resMes['restaurant_cart']['address_err']);
        }

        if (!$restaurantBranch->restaurant->is_enable) {
            throw new ForbiddenException(sprintf($this->resMes['restaurant']['enable'], $restaurantBranch->restaurant->name));
        }

        if (!$restaurantBranch->is_enable) {
            throw new ForbiddenException(sprintf($this->resMes['restaurant_branch']['enable'], $restaurantBranch->name));
        }

        foreach ($menuIds as $menuId) {
            $menu = Menu::where('id', $menuId)->first();

            if (!$menu->is_enable) {
                throw new ForbiddenException(sprintf($this->resMes['menu']['enable'], $menu->name));
            }
        }
    }

    private function getOrderItems($menuCartItems)
    {
        return $menuCartItems->map(function ($cartItem) {
            return [
                'slug' => $cartItem->menu['slug'],
                'quantity' => $cartItem->menu['quantity'],
                'variant_slug' => $cartItem->menu['variant']['slug'],
                'topping_slugs' => collect($cartItem->menu['toppings'])->map(function ($value) {
                    $value['value'] = $value['quantity'];
                    unset($value['quantity']);
                    return $value;
                })->toArray(),
                'option_items' => collect($cartItem->menu['options'])->pluck('slug')->toArray(),
            ];
        })->toArray();
    }
}
