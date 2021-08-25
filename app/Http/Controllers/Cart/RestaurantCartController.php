<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Admin\v3\RestaurantOrderController;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\MenuCart;
use App\Models\MenuCartItem;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
use App\Models\RestaurantBranch;
use App\Services\MessageService\BoomSmsService;
use App\Services\MessageService\SlackMessagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestaurantCartController extends Controller
{
    use ResponseHelper;

    public function viewCart(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $customer->id)->first();

        $data = [
            'restaurant' => $menuCart ? $this->prepareMenuCartData($menuCart, $customer) : [],
            'shop' => [],
        ];

        return $this->generateResponse($data, 200);
    }

    public function store(Request $request, Menu $menu)
    {
        $validator = $this->validateMenuCart($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        try {
            $customer = Customer::where('slug', $request->customer_slug)->first();
            $restaurantBranch = RestaurantBranch::where('slug', $request->restaurant_branch_slug)->first();
            $menuCart = MenuCart::where('customer_id', $customer->id)->first();
            $menuData = $this->prepareMenuData($menu, $request);

            if ($menuCart) {
                if ($menuCart->restaurant_branch_id !== $restaurantBranch->id) {
                    return $this->generateResponse('You can only order from same branch.', 400, true);
                }

                $menuCartItem = MenuCartItem::where('menu_cart_id', $menuCart->id)->where('menu_id', $menu->id)->first();

                if ($menuCartItem) {
                    $menuCartItem->menu = $menuData;
                    $menuCartItem->save();
                } else {
                    $this->createMenuCartItem($menuCart->id, $menu->id, $menuData);
                }
            } else {
                $menuCart = DB::transaction(function () use ($customer, $restaurantBranch, $menu, $menuData) {
                    $menuCart = $this->createMenuCart($customer->id, $restaurantBranch->id);
                    $this->createMenuCartItem($menuCart->id, $menu->id, $menuData);
                    return $menuCart;
                });
            }

            $data = $this->prepareMenuCartData($menuCart->load('menuCartItems'), $customer);
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
            'slug' => $menu->slug,
            'name' => $menu->name,
            'description' => $menu->description,
            'amount' => $amount,
            'tax' => $tax,
            'discount' => $discount,
            'quantity' => $request->quantity,
            'variant' => $menuVariant,
            'toppings' => $toppings,
        ];
    }

    private function getVariant($menu, $variantSlug)
    {
        $menuVariant = MenuVariant::where('menu_id', $menu->id)->where('slug', $variantSlug)->first();

        if (!$menuVariant) {
            throw new BadRequestException('The variant must be part of the menu.');
        }

        return $menuVariant;
    }

    private function getToppings($menu, $toppings)
    {
        $menuToppings = [];

        foreach ($toppings as $key => $value) {
            $menuTopping = MenuTopping::where('slug', $value['slug'])->first();

            if ($menuTopping->menu_id != $menu->id) {
                throw new BadRequestException('The selected toppings.' . $key . ' must be part of the menu.');
            }

            if ($value['quantity'] > $menuTopping->max_quantity) {
                throw new BadRequestException('The selected toppings.' . $key . '.quantity cannot be higher than ' . $value['quantity'] . '.');
            }

            $menuToppings[] = [
                'slug' => $menuTopping->slug,
                'value' => $value['quantity'],
            ];
        }

        return $menuToppings;
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

    private function prepareMenuCartData($menuCart, $customer)
    {
        return [
            'slug' => $menuCart->slug,
            'promocode' => $menuCart->promocode,
            'promo_amount' => $menuCart->promo_amount,
            'total_amount' => $this->getTotalAmount($menuCart->menuCartItems->pluck('menu'), $menuCart->promo_amount),
            'menus' => $menuCart->menuCartItems->pluck('menu'),
            'address' => $customer->primary_address,
        ];
    }

    private function getTotalAmount($menuCartItems, $promoAmount)
    {
        $totalAmount = 0;

        foreach ($menuCartItems as $item) {
            $amount = ($item['amount'] + $item['tax'] - $item['discount']) * $item['quantity'];
            $totalAmount += $amount;
        }

        return $totalAmount - $promoAmount;
    }

    public function delete(Request $request, Menu $menu)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuCart = MenuCart::where('customer_id', $customer->id)->first();

        if ($menuCart) {
            MenuCartItem::where('menu_cart_id', $menuCart->id)->where('menu_id', $menu->id)->delete();
        }

        $data = $this->prepareMenuCartData($menuCart->load('menuCartItems'), $customer);
        return $this->generateResponse($data, 200);
    }

    public function deleteCart(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuCart = MenuCart::where('customer_id', $customer->id)->first();

        if ($menuCart) {
            $menuCart->delete();
        }

        return $this->generateResponse('success', 200, true);
    }

    public function checkout(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $customer->id)->firstOrFail();

        if ($menuCart && $menuCart->menuCartItems->count() === 0) {
            return $this->generateResponse('Your cart is empty.', 400, true);
        }

        $request['restaurant_branch_slug'] = RestaurantBranch::where('id', $menuCart->restaurant_branch_id)->value('slug');
        $request['promo_code'] = $menuCart->promocode;

        $request['order_items'] = $menuCart->menuCartItems->map(function ($cartItem) {
            return [
                'slug' => $cartItem->menu['slug'],
                'quantity' => $cartItem->menu['quantity'],
                'variant_slug' => $cartItem->menu['variant']['slug'],
                'topping_slugs' => $cartItem->menu['toppings'],
            ];
        })->toArray();

        if (App::environment('production')) {
            $messageService = new BoomSmsService();
        } else {
            $messageService = new SlackMessagingService();
        }

        $order = new RestaurantOrderController($messageService);

        $result = $order->store($request);
        
        return $result;
    }
}
