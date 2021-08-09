<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RestaurantOrderController extends Controller
{
    public function getAllOrders(Request $request)
    {
        $request->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d',
        ]);

        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
            ->whereBetween('order_date', [$request->from, $request->to])
            ->orderBy('restaurant_id')
            ->orderBy('restaurant_branch_id')
            ->orderBy('id')
            ->paginate(10);

        return $this->generateReport($restaurantOrders);
    }

    public function getVendorOrders(Request $request, $slug)
    {
        $request->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d',
        ]);

        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
            ->where('restaurant_id', $restaurant->id)
            ->whereBetween('order_date', [$request->from, $request->to])
            ->orderBy('restaurant_id')
            ->orderBy('restaurant_branch_id')
            ->orderBy('id')
            ->paginate(10);

        return $this->generateReport($restaurantOrders);
    }

    public function getBranchOrders(Request $request, $slug)
    {
        $request->validate([
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d',
        ]);

        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
            ->where('restaurant_branch_id', $restaurantBranch->id)
            ->whereBetween('order_date', [$request->from, $request->to])
            ->orderBy('restaurant_id')
            ->orderBy('restaurant_branch_id')
            ->orderBy('id')
            ->paginate(10);

        return $this->generateReport($restaurantOrders);
    }

    private function generateReport($restaurantOrders)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;

        foreach ($restaurantOrders->items() as $order) {
            $restaurant = Restaurant::where('id', $order->restaurant_id)->first();

            $amount = $order->order_status == 'cancelled' ? 0 : $order->amount;
            $commission = $amount * $restaurant->commission * 0.01;
            $commissionCt = $commission * 0.05;
            $totalAmount = $order->order_status == 'cancelled' ? 0 : $order->total_amount;
            $balance = $totalAmount - $commissionCt;

            $amountSum += $amount;
            $totalAmountSum += $totalAmount;
            $commissionSum += $commission;
            $commissionCtSum += $commissionCt;
            $balanceSum += $balance;

            $data[] = [
                'invoice_id' => $order->invoice_id,
                'order_date' => Carbon::parse($order->order_date)->format('M d Y h:i a'),
                'customer_name' => $order->restaurantOrderContact->customer_name,
                'phone_number' => $order->restaurantOrderContact->phone_number,
                'restaurant' => $restaurant->name,
                'branch' => RestaurantBranch::where('id', $order->restaurant_branch_id)->value('name'),
                'revenue' => $amount,
                'commercial_tax' => $order->order_status != 'cancelled' && $order->tax ? $order->tax : 0,
                'discount' => $order->order_status != 'cancelled' && $order->discount ? $order->discount : 0,
                'promo_discount' => $order->order_status != 'cancelled' && $order->promocode_amount ? $order->promocode_amount : 0,
                'total_amount' => $totalAmount,
                'commission_rate' => $restaurant->commission ? $restaurant->commission : 0,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
                'payment_mode' => $order->payment_mode,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'order_type' => $order->order_type,
                'special_instructions' => $order->special_instruction,
            ];
        }

        $result = [
            'revenue_sum' => $amountSum,
            'total_amount_sum' => $totalAmountSum,
            'commission_sum' => $commissionSum,
            'commission_ct_sum' => $commissionCtSum,
            'balance_sum' => $balanceSum,
            'total' => $restaurantOrders->total(),
            'invoice' => $data,
        ];

        return $result;
    }
}
