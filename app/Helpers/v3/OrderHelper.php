<?php

namespace App\Helpers\v3;

use App\Exceptions\ForbiddenException;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait OrderHelper
{
    public static function getPromoData($validatedData, $customer)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest()->first();
        if (!$promocode) {
            throw new ForbiddenException('Promocode not found.');
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, 'shop');
        if (!$validUsage) {
            throw new ForbiddenException('Invalid promocode usage for shop.');
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, 'shop');
        if (!$validRule) {
            throw new ForbiddenException('Invalid promocode.');
        }

        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], 'shop');

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = min($validatedData['subTotal'] + $validatedData['tax'], $promocodeAmount);

        return $validatedData;
    }

    public static function checkCredit($validatedData)
    {
        $totalAmount = self::getTotalAmount($validatedData['order_items'], isset($validatedData['promocode_amount']) ? $validatedData['promocode_amount'] : 0) + $validatedData['delivery_fee'];

        if ($totalAmount > self::getRemainingCredit($validatedData['customerId'])) {
            throw new ForbiddenException('Insufficient credit.');
        }

        $validatedData['payment_status'] = 'success';
        return $validatedData;
    }

    public static function getRemainingCredit($customerId)
    {
        $restaurantOrders = DB::table('restaurant_orders')
            ->select('id', 'created_at', DB::raw("'restaurant' as source"))
            ->where('customer_id', $customerId)
            ->where('payment_mode', 'Credit')
            ->where('payment_status', 'success')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);

        $orders = DB::table('shop_orders')
            ->select('id', 'created_at', DB::raw("'shop' as source"))
            ->where('customer_id', $customerId)
            ->where('payment_mode', 'Credit')
            ->where('payment_status', 'success')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->union($restaurantOrders)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalUsage = $orders->map(function ($item) {
            if ($item->source === 'restaurant') {
                return RestaurantOrder::where('id', $item->id)->first();
            } else {
                return ShopOrder::where('id', $item->id)->first();
            }
        })->sum('total_amount');

        $credit = Credit::where('customer_id', $customerId)->first();
        return $credit ? $credit->amount - $totalUsage : 0;
    }

    public static function getTotalAmount($cartItems, $promoAmount)
    {
        $totalAmount = 0;

        foreach ($cartItems as $item) {
            $totalAmount += ($item['amount'] + $item['tax'] - $item['discount']) * $item['quantity'];
        }

        return $totalAmount - $promoAmount;
    }

    public static function getCustomerPhoneNumber($customerId)
    {
        return Customer::where('id', $customerId)->value('phone_number');
    }
}
