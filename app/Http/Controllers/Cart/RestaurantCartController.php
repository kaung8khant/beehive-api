<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\CacheHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Admin\v3\RestaurantOrderController;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\MenuCart;
use App\Models\MenuCartItem;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
use App\Models\Promocode;
use App\Models\RestaurantBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
        $this->customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
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

            if ($menuCart) {
                if ($menuCart->restaurant_branch_id !== $restaurantBranch->id) {
                    $sameBranchError = $this->resMes['restaurant_cart']['same_branch_err'];
                    return $this->generateResponse($sameBranchError, 400, true);
                }

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
                $menuCart = DB::transaction(function () use ($restaurantBranch, $menu, $menuData) {
                    $menuCart = $this->createMenuCart($this->customer->id, $restaurantBranch->id);
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
            'toppings.*.slug' => 'required|exists:App\Models\MenuTopping',
            'toppings.*.quantity' => 'required|integer',
        ]);
    }

    private function prepareMenuData($menu, $request)
    {
        $menuVariant = $this->getVariant($menu, $request->variant_slug);
        $toppings = $this->getToppings($menu, $request->toppings);

        $amount = $menuVariant->price + collect($toppings)->sum('price');
        $tax = ($amount - $menuVariant->discount) * $menuVariant->tax * 0.01;
        $discount = $menuVariant->discount;

        return [
            'key' => $this->getMenuKey($menu->slug, $menuVariant->slug, $toppings),
            'slug' => $menu->slug,
            'name' => $menu->name,
            'description' => $menu->description,
            'amount' => $amount,
            'tax' => $tax,
            'discount' => $discount,
            'quantity' => $request->quantity,
            'variant' => $menuVariant,
            'toppings' => $toppings,
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
        $menuToppings = [];

        foreach ($toppings as $key => $value) {
            $menuTopping = MenuTopping::where('slug', $value['slug'])->first();

            if ($menuTopping->menu_id != $menu->id) {
                throw new BadRequestException(sprintf($this->resMes['restaurant_cart']['topping_err'], $key));
            }

            if ($value['quantity'] > $menuTopping->max_quantity) {
                throw new BadRequestException(sprintf($this->resMes['restaurant_cart']['topping_qty_err'], $key, $menuTopping->max_quantity));
            }

            $menuToppings[] = [
                'slug' => $menuTopping->slug,
                'quantity' => $value['quantity'],
                'price' => $menuTopping->price,
            ];
        }

        return collect($menuToppings)->sortBy('slug')->values();
    }

    private function getMenuKey($menuSlug, $menuVariantSlug, $toppings)
    {
        return $menuSlug . '-' . $menuVariantSlug . '-' . implode('-', $toppings->pluck('slug')->toArray()) . '-' . implode('-', $toppings->pluck('quantity')->toArray());
    }

    private function createMenuCart($customerId, $restaurantBranchId)
    {
        return MenuCart::create([
            'slug' => StringHelper::generateUniqueSlug(),
            'customer_id' => $customerId,
            'restaurant_branch_id' => $restaurantBranchId,
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

        try {
            $menuIds = $menuCart->menuCartItems->pluck('menu_id')->unique();
            $this->checkAddressAndBranch($request, $menuIds, $menuCart->restaurant_branch_id);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }

        $request['restaurant_branch_slug'] = RestaurantBranch::where('id', $menuCart->restaurant_branch_id)->value('slug');
        $request['promo_code'] = $menuCart->promocode;
        $request['order_items'] = $this->getOrderItems($menuCart->menuCartItems);

        $order = new RestaurantOrderController($this->getMessageService(), $this->getPaymentService($request->payment_mode));
        $result = $order->store($request);

        if (json_decode($result->getContent(), true)['status'] === 201) {
            $menuCart->delete();
        }

        return $result;
    }

    public function checkAddress(Request $request)
    {
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $this->customer->id)->first();

        if (!$menuCart || !isset($menuCart->menuCartItems) || $menuCart->menuCartItems->count() === 0) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        try {
            $menuIds = $menuCart->menuCartItems->pluck('menu_id')->unique();
            $this->checkAddressAndBranch($request, $menuIds, $menuCart->restaurant_branch_id);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }

        return $this->generateResponse($this->resMes['restaurant_cart']['address_succ'], 200, true);
    }

    private function checkAddressAndBranch($request, $menuIds, $restaurantBranchId)
    {
        $restaurantBranch = RestaurantBranch::selectRaw('id, slug, name, address, contact_number, opening_time, closing_time, is_enable, restaurant_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->address['latitude'], $request->address['longitude'], $request->address['latitude']])
            ->where('id', $restaurantBranchId)
            ->first();

        if ($restaurantBranch->distance > CacheHelper::getRestaurantSearchRadius()) {
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
            ];
        })->toArray();
    }
}
