<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\PromocodeHelper;
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
use App\Models\Promocode;
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

    private $customer;
    private $resMes;

    public function __construct(Request $request)
    {
        $this->customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $this->resMes = config('response-en');
    }

    public function viewCart()
    {
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $this->customer->id)->first();

        $data = [
            'restaurant' => $menuCart ? $this->prepareMenuCartData($menuCart, $this->customer) : [],
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
            $restaurantBranch = RestaurantBranch::where('slug', $request->restaurant_branch_slug)->first();
            $menuCart = MenuCart::where('customer_id', $this->customer->id)->first();
            $menuData = $this->prepareMenuData($menu, $request);

            if ($menuCart) {
                if ($menuCart->restaurant_branch_id !== $restaurantBranch->id) {
                    $sameBranchError = $this->resMes['restaurant_cart']['same_branch_err'];
                    return $this->generateResponse($sameBranchError, 400, true);
                }

                $menuCartItem = MenuCartItem::where('menu_cart_id', $menuCart->id)->where('menu_id', $menu->id)->first();

                if ($menuCartItem) {
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

            $data = $this->prepareMenuCartData($menuCart->refresh()->load('menuCartItems'), $this->customer);
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
                'value' => $value['quantity'],
                'price' => $menuTopping->price,
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
            'sub_total' => $this->getSubTotal($menuCart->menuCartItems->pluck('menu')),
            'promo_amount' => $menuCart->promo_amount,
            'total_amount' => $this->getTotalAmount($menuCart->menuCartItems->pluck('menu'), $menuCart->promo_amount),
            'menus' => $menuCart->menuCartItems->pluck('menu'),
            'address' => $customer->primary_address,
        ];
    }

    private function getSubTotal($menuCartItems)
    {
        $subTotal = 0;

        foreach ($menuCartItems as $item) {
            $amount = ($item['amount'] - $item['discount']) * $item['quantity'];
            $subTotal += $amount;
        }

        return $subTotal;
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
        $menuCart = MenuCart::where('customer_id', $this->customer->id)->first();

        if ($menuCart) {
            MenuCartItem::where('menu_cart_id', $menuCart->id)->where('menu_id', $menu->id)->delete();
        }

        $data = $this->prepareMenuCartData($menuCart->load('menuCartItems'), $this->customer);
        return $this->generateResponse($data, 200);
    }

    public function deleteCart(Request $request)
    {
        $menuCart = MenuCart::where('customer_id', $this->customer->id)->first();

        if ($menuCart) {
            $menuCart->delete();
        }

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

            $promoData = $this->getPromoData($request, $this->customer);

            $menuCart->promocode_id = $promoData['promocode_id'];
            $menuCart->promocode = $promoData['promocode'];
            $menuCart->promo_amount = $promoData['promo_amount'];
            $menuCart->save();

            $cartData = $this->prepareMenuCartData($menuCart, $this->customer);
            return $this->generateResponse($cartData, 200);

        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 403, true);
        }
    }

    private function getPromoData($request, $customer)
    {
        $resMessages = $this->resMes['promo_code'];

        $promocode = Promocode::where('code', strtoupper($request->promo_code))->with('rules')->latest('created_at')->first();
        if (!$promocode) {
            throw new ForbiddenException($resMessages['not_found']);
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'restaurant');
        if (!$validUsage) {
            throw new ForbiddenException($resMessages['invalid_res']);
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $request->order_items, $request->sub_total, $customer, 'restaurant');
        if (!$validRule) {
            throw new ForbiddenException($resMessages['invalid']);
        }

        $promoAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $request->order_items, $request->sub_total, 'restaurant');

        return [
            'promocode_id' => $promocode->id,
            'promocode' => $promocode->code,
            'promo_amount' => $promoAmount,
        ];
    }

    public function changeAddress(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
    }

    public function checkout(Request $request)
    {
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $this->customer->id)->first();

        if (!$menuCart || !isset($menuCart->menuCartItems) || $menuCart->menuCartItems->count() === 0) {
            return $this->generateResponse($this->resMes['restaurant_cart']['empty'], 400, true);
        }

        $request['restaurant_branch_slug'] = RestaurantBranch::where('id', $menuCart->restaurant_branch_id)->value('slug');
        $request['promo_code'] = $menuCart->promocode;
        $request['order_items'] = $this->getOrderItems($menuCart->menuCartItems);

        if (App::environment('production')) {
            $messageService = new BoomSmsService();
        } else {
            $messageService = new SlackMessagingService();
        }

        $order = new RestaurantOrderController($messageService);

        $result = $order->store($request);

        if (json_decode($result->getContent(), true)['status'] === 201) {
            $menuCart->delete();
        }

        return $result;
    }

    private function getOrderItems($menuCartItems)
    {
        return $menuCartItems->map(function ($cartItem) {
            return [
                'slug' => $cartItem->menu['slug'],
                'quantity' => $cartItem->menu['quantity'],
                'variant_slug' => $cartItem->menu['variant']['slug'],
                'topping_slugs' => $cartItem->menu['toppings'],
            ];
        })->toArray();
    }
}
