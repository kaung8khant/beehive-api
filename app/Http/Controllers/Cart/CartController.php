<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\ForbiddenException;
use App\Helpers\GeoHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MenuCart;
use App\Models\ProductCart;
use App\Models\Promocode;
use App\Models\RestaurantBranch;
use App\Models\Shop;
use App\Services\MessageService\BoomSmsService;
use App\Services\MessageService\SlackMessagingService;
use App\Services\PaymentService\CbPayService;
use App\Services\PaymentService\CodService;
use App\Services\PaymentService\KbzPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use stdClass;

class CartController extends Controller
{
    use ResponseHelper;

    public function viewCart(Request $request)
    {
        $customer = Customer::where('slug', $request->customer_slug)->firstOrFail();
        $menuCart = MenuCart::with('menuCartItems')->where('customer_id', $customer->id)->first();
        $productCart = ProductCart::with('productCartItems')->where('customer_id', $customer->id)->first();

        $data = [
            'restaurant' => $menuCart ? $this->prepareMenuCartData($menuCart) : new stdClass(),
            'shop' => $productCart ? $this->prepareProductCartData($productCart) : new stdClass(),
        ];

        return $this->generateResponse($data, 200);
    }

    protected function prepareMenuCartData($menuCart)
    {
        $branch = RestaurantBranch::with('restaurant')->where('id', $menuCart->restaurant_branch_id)->first();

        $address = $menuCart->address;
        $distance = GeoHelper::calculateDistance($address['latitude'], $address['longitude'], $branch->latitude, $branch->longitude);
        $deliveryFee = GeoHelper::calculateDeliveryFee($distance);

        return [
            'slug' => $menuCart->slug,
            'restaurant' => $branch->restaurant->makeHidden('created_by', 'updated_by', 'covers'),
            'restaurant_branch' => $branch->makeHidden('restaurant', 'created_by', 'updated_by'),
            'address' => $menuCart->address,
            'distance' => $distance,
            'delivery_time' => GeoHelper::calculateDeliveryTime($distance),
            'delivery_fee' => $deliveryFee,
            'promocode' => $menuCart->promocode,
            'sub_total' => $this->getSubTotal($menuCart->menuCartItems->pluck('menu')),
            'total_tax' => $this->getTotalTax($menuCart->menuCartItems->pluck('menu')),
            'promo_amount' => $menuCart->promo_amount,
            'total_amount' => $this->getTotalAmount($menuCart->menuCartItems->pluck('menu'), $menuCart->promo_amount) + $deliveryFee,
            'menus' => $menuCart->menuCartItems->pluck('menu'),
        ];
    }

    protected function prepareProductCartData($productCart)
    {
        $shopIds = $productCart->productCartItems->pluck('shop_id')->unique();

        return [
            'slug' => $productCart->slug,
            'promocode' => $productCart->promocode,
            'sub_total' => $this->getSubTotal($productCart->productCartItems->pluck('product')),
            'total_tax' => $this->getTotalTax($productCart->productCartItems->pluck('product')),
            'promo_amount' => $productCart->promo_amount,
            'total_amount' => $this->getTotalAmount($productCart->productCartItems->pluck('product'), $productCart->promo_amount),
            'shops' => $this->getProductsWithShop($productCart, $shopIds),
        ];
    }

    protected function getSubTotal($cartItems)
    {
        $subTotal = 0;

        foreach ($cartItems as $item) {
            $subTotal += ($item['amount'] - $item['discount']) * $item['quantity'];
        }

        return $subTotal;
    }

    private function getTotalTax($cartItems)
    {
        $tax = 0;

        foreach ($cartItems as $item) {
            $tax += $item['tax'] * $item['quantity'];
        }

        return $tax;
    }

    private function getTotalAmount($cartItems, $promoAmount)
    {
        $totalAmount = 0;

        foreach ($cartItems as $item) {
            $totalAmount += ($item['amount'] + $item['tax'] - $item['discount']) * $item['quantity'];
        }

        return $totalAmount - $promoAmount;
    }

    private function getProductsWithShop($productCart, $shopIds)
    {
        $shops = [];

        foreach ($shopIds as $shopId) {
            $shop = Shop::where('id', $shopId)->first()->makeHidden('created_by', 'updated_by');
            $shop['products'] = $productCart->productCartItems->where('shop_id', $shopId)->pluck('product');

            $shops[] = $shop;
        }

        return $shops;
    }

    protected function getPromoData($request, $customer, $type)
    {
        $resMessages = config('response-en.promo_code');

        $promocode = Promocode::where('code', strtoupper($request->promo_code))->with('rules')->latest('created_at')->first();
        if (!$promocode) {
            throw new ForbiddenException($resMessages['not_found']);
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, $type);
        if (!$validUsage) {
            $invalidMes = $type === 'shop' ? $resMessages['invalid_shop'] : $resMessages['invalid_rest'];
            throw new ForbiddenException($invalidMes);
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $request->order_items, $request->sub_total, $customer, $type);
        if (!$validRule) {
            throw new ForbiddenException($resMessages['invalid']);
        }

        $promoAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $request->order_items, $request->sub_total, $type);

        return [
            'promocode_id' => $promocode->id,
            'promocode' => $promocode->code,
            'promo_amount' => $promoAmount,
        ];
    }

    protected function getMessageService()
    {
        if (App::environment('production')) {
            return new BoomSmsService();
        } else {
            return new SlackMessagingService();
        }
    }

    protected function getPaymentService($paymentMode)
    {
        if ($paymentMode === 'KPay') {
            return new KbzPayService();
        } elseif ($paymentMode === 'CBPay') {
            return new CbPayService();
        } else {
            return new CodService();
        }
    }
}
