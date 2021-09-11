<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    public function getPromocodeReport(Request $request)
    {
        $data = [];
        $totalAmountSum = 0;
        $shopOrders = ShopOrder::whereBetween('order_date', [$request->from, $request->to])
            ->select('promocode_id', 'promocode', 'promocode_amount')
            ->get();
        $restaurantOrders = RestaurantOrder::whereBetween('order_date', [$request->from, $request->to])
            ->select('promocode_id', 'promocode', 'promocode_amount')
            ->get();
        $orderList = collect($shopOrders)->merge($restaurantOrders)->groupBy('promocode_id');
        foreach ($orderList as $key => $group) {
            $amount = 0;
            foreach ($group as $k => $order) {
                $amount += $order->promocode_amount ? $order->promocode_amount : 0;
            }
            $totalAmountSum += $amount;
            $promocode=Promocode::where('id', $group[0]->promocode_id)->first();
            $data[] = [
                'slug' => $promocode ?  $promocode->slug : null,
                'promocode' => $group[0]->promocode,
                'count' => $group->count(),
                'amount' => $amount
            ];
        }

        $result = [
            'total_amount_sum' => $totalAmountSum,
            'invoice' => $data,
        ];
        return $result;
    }
}
