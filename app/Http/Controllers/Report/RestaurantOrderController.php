<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RestaurantOrderController extends Controller
{
    public function getAllOrders(Request $request)
    {
        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
        ->orderBy('restaurant_id')
        ->orderBy('restaurant_branch_id')
        ->orderBy('id');

        $restaurantOrders=$this->filterByDate($restaurantOrders, $request->from, $request->to, $request->filterBy);
        return $this->generateReport($restaurantOrders, $request->from, $request->to, $request->filterBy);
    }

    public function getVendorOrders(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
                ->where('restaurant_id', $restaurant->id)
                ->orderBy('restaurant_id')
                ->orderBy('restaurant_branch_id')
                ->orderBy('id');
        $restaurantOrders=$this->filterByDate($restaurantOrders, $request->from, $request->to, $request->filterBy);
        return $this->generateReport($restaurantOrders, $request->from, $request->to, $request->filterBy);
    }

    public function getBranchOrders(Request $request, $slug)
    {
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
            ->where('restaurant_branch_id', $restaurantBranch->id)
            ->orderBy('restaurant_id')
            ->orderBy('restaurant_branch_id')
            ->orderBy('id');
        $restaurantOrders=$this->filterByDate($restaurantOrders, $request->from, $request->to, $request->filterBy);
        return $this->generateReport($restaurantOrders, $request->from, $request->to, $request->filterBy);
    }

    private function filterByDate($restaurantOrders, $from = null, $to = null, $filterBy = null)
    {
        if ($filterBy === 'orderDate') {
            $restaurantOrders =$restaurantOrders->whereBetween('order_date', [$from, $to])->get();
        } elseif ($filterBy === 'deliveredDate') {
            $restaurantOrders =$restaurantOrders->whereHas('restaurantOrderStatuses', function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to])->where('status', '=', 'delivered')->orderBy('created_at', 'desc');
            })->get();
        } else {
            $restaurantOrders =$restaurantOrders->whereHas('restaurantOrderStatuses', function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to])->where('status', '=', 'pickUp')->orderBy('created_at', 'desc');
            })->get();
        }

        return $restaurantOrders;
    }

    private function generateReport($restaurantOrders, $from = null, $to = null, $filterBy = null)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;

        foreach ($restaurantOrders as $order) {
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

            $orderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'delivered')->orderBy('created_at', 'desc')->first();
            $invoiceOrderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'pickUp')->orderBy('created_at', 'desc')->first();

            if ($filterBy === 'deliveredDate') {
                $orderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'delivered')->whereBetween('created_at', array($from, $to))->orderBy('created_at', 'desc')->first();
            }
            if ($filterBy === 'invoiceDate') {
                $invoiceOrderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'pickUp')->whereBetween('created_at', array($from, $to))->orderBy('created_at', 'desc')->first();
            }

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
                'delivery_fee' => $order->order_status != 'cancelled' && $order->delivery_fee ? $order->delivery_fee : 0,
                'total_amount' => $totalAmount,
                'commission_rate' => $restaurant->commission ? $restaurant->commission : 0,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
                'payment_mode' => $order->payment_mode,
                'payment_status' => $order->payment_status,
                'payment_reference' => $order->payment_reference,
                'order_status' => $order->order_status,
                'order_type' => $order->order_type,
                'special_instructions' => $order->special_instruction,
                'delivered_date' => $orderStatus ? Carbon::parse($orderStatus->created_at)->format('M d Y h:i a') : null,
                'invoice_date' => $invoiceOrderStatus ? Carbon::parse($invoiceOrderStatus->created_at)->format('M d Y h:i a') : null,
            ];
        }

        $result = [
            'revenue_sum' => $amountSum,
            'total_amount_sum' => $totalAmountSum,
            'commission_sum' => $commissionSum,
            'commission_ct_sum' => $commissionCtSum,
            'balance_sum' => $balanceSum,
            'total' => sizeof($restaurantOrders),
            'invoice' => $data,
        ];

        return $result;
    }
}
