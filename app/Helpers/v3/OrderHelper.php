<?php

namespace App\Helpers\v3;

use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait OrderHelper
{
    public static function getRemainingCredit($customer)
    {
        $restaurantOrders = DB::table('restaurant_orders')
            ->select('id', 'created_at', DB::raw("'restaurant' as source"))
            ->where('customer_id', $customer->id)
            ->where('payment_mode', 'Credit')
            ->where('payment_status', 'success')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);

        $orders = DB::table('shop_orders')
            ->select('id', 'created_at', DB::raw("'shop' as source"))
            ->where('customer_id', $customer->id)
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

        return $customer->credit->amount - $totalUsage;
    }

    public static function getTotalAmount($cartItems, $promoAmount)
    {
        $totalAmount = 0;

        foreach ($cartItems as $item) {
            $totalAmount += ($item['amount'] + $item['tax'] - $item['discount']) * $item['quantity'];
        }

        return $totalAmount - $promoAmount;
    }
}
