<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\MenuCart;
use App\Models\MenuCartItem;
use App\Models\MenuTopping;
use App\Models\MenuVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestaurantCartController extends Controller
{
    use ResponseHelper;

    public function store(Request $request, Menu $menu)
    {
        $validator = Validator::make($request->all(), [
            'customer_slug' => 'required|exists:App\Models\Customer,slug',
            'variant' => 'required|array',
            'variant.*.name' => 'required',
            'variant.*.value' => 'required',
            'toppings' => 'nullable|array',
            'toppings.*.slug' => 'required|exists:App\Models\MenuTopping',
            'toppings.*.quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();

        try {
            $menuCart = MenuCart::where('customer_id', $customer->id)->first();
            $menuData = $this->prepareMenuData($menu, $request);

            if ($menuCart) {
                $menuCartItem = MenuCartItem::where('menu_cart_id', $menuCart->id)->where('menu_id', $menu->id)->first();

                if ($menuCartItem) {
                    $menuCartItem->menu = $menuData;
                    $menuCartItem->save();
                } else {
                    $this->createMenuCartItem($menuCart->id, $menu->id, $menuData);
                }
            } else {
                $menuCart = DB::transaction(function () use ($customer, $menu, $menuData) {
                    $menuCart = $this->createMenuCart($customer->id);
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

    private function prepareMenuData($menu, $request)
    {
        $menuVariant = $this->getVariant($menu, $request->variant);
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

    private function getVariant($menu, $variant)
    {
        $menuVariants = MenuVariant::where('menu_id', $menu->id)->get();

        $menuVariant = $menuVariants->first(function ($value) use ($variant) {
            return $variant == $value->variant;
        });

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
                throw new BadRequestException('The selected toppings.' . $key . '.must be part of the menu.');
            }

            $menuToppings[] = $menuTopping;
        }

        return $menuToppings;
    }

    private function createMenuCart($customerId)
    {
        return MenuCart::create([
            'slug' => StringHelper::generateUniqueSlug(),
            'customer_id' => $customerId,
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

    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //

    public function update(Request $request, Menu $menu)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuVariant = MenuVariant::where('slug', $request->variant_slug)->firstOrFail();
        $toppings = MenuTopping::whereIn('slug', $request->topping_slugs)->get();

        $data = [
            'slug' => StringHelper::generateUniqueSlug(),
            'amount' => $menuVariant->price,
            'tax' => $menuVariant->tax,
            'discount' => $menuVariant->discount,
            'promocode' => null,
            'promocode_amount' => 0,
            'commission' => 0,
            'total_amount' => $menuVariant->price,
            'menus' => [
                [
                    'slug' => $menu->slug,
                    'name' => $menu->name,
                    'description' => $menu->description,
                    'variant' => $menuVariant,
                    'toppings' => $toppings,
                ],
            ],
            'address' => $customer->primary_address,
        ];

        return $this->generateResponse($data, 200);
    }

    public function viewCart(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menu = Menu::where('slug', 'E0FBE079')->firstOrFail();
        $menuVariant = MenuVariant::where('slug', 'E857AEB9')->firstOrFail();
        $toppings = MenuTopping::whereIn('slug', ['E5BCA2D6'])->get();

        $data = [
            'restaurant' => [
                'slug' => StringHelper::generateUniqueSlug(),
                'amount' => $menuVariant->price,
                'tax' => $menuVariant->tax,
                'discount' => $menuVariant->discount,
                'promocode' => null,
                'promocode_amount' => 0,
                'commission' => 0,
                'total_amount' => $menuVariant->price,
                'menus' => [
                    [
                        'slug' => $menu->slug,
                        'name' => $menu->name,
                        'description' => $menu->description,
                        'variant' => $menuVariant,
                        'toppings' => $toppings,
                    ],
                ],
                'address' => $customer->primary_address],
            'shop' => [],
        ];

        return $this->generateResponse($data, 200);
    }

    public function delete(Request $request, Menu $menu)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuVariant = MenuVariant::where('slug', 'E857AEB9')->firstOrFail();
        $toppings = MenuTopping::whereIn('slug', ['E5BCA2D6'])->get();

        $data = [
            'slug' => StringHelper::generateUniqueSlug(),
            'amount' => $menuVariant->price,
            'tax' => $menuVariant->tax,
            'discount' => $menuVariant->discount,
            'promocode' => null,
            'promocode_amount' => 0,
            'commission' => 0,
            'total_amount' => $menuVariant->price,
            'menus' => [
                [
                    'slug' => $menu->slug,
                    'name' => $menu->name,
                    'description' => $menu->description,
                    'variant' => $menuVariant,
                    'toppings' => $toppings,
                ],
            ],
            'address' => $customer->primary_address,
        ];

        return $this->generateResponse($data, 200);
    }

    public function deleteCart(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();

        return $this->generateResponse('success', 200, true);
    }
}
