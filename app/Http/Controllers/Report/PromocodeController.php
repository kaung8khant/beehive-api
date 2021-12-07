<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    public function getPromocodeReport(Request $request)
    {
        $data = [];
        $totalAmountSum = 0;
        $shopOrders = ShopOrder::whereBetween('order_date', [$request->from, $request->to])
            ->where('order_status', '!=', 'cancelled')
            ->whereNotNull('promocode')
            ->select('promocode_id', 'promocode', 'promocode_amount')
            ->get();
        $restaurantOrders = RestaurantOrder::whereBetween('order_date', [$request->from, $request->to])
            ->where('order_status', '!=', 'cancelled')
            ->whereNotNull('promocode')
            ->select('promocode_id', 'promocode', 'promocode_amount')
            ->get();
        $orderList = collect($shopOrders)->merge($restaurantOrders)->groupBy('promocode_id');
        foreach ($orderList as $key => $group) {
            $amount = 0;
            foreach ($group as $k => $order) {
                $amount += $order->promocode_amount ? $order->promocode_amount : 0;
            }
            $totalAmountSum += $amount;
            $promocode = Promocode::where('id', $group[0]->promocode_id)->first();
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

    public function getPromocodeUsedInvoiceReport(Request $request, Promocode $promocode)
    {
        $shopOrders = ShopOrder::where('promocode_id', $promocode->id)
            ->whereBetween('order_date', [$request->from, $request->to])
            ->get();
        $restaurantOrders = RestaurantOrder::where('promocode_id', $promocode->id)
            ->whereBetween('order_date', [$request->from, $request->to])
            ->get();
        $orderList = collect($shopOrders)->merge($restaurantOrders);
        return $this->generateReport($orderList);
    }

    public function getPromocodeUsedCustomerReport(Request $request, Promocode $promocode)
    {
        $shopOrders = ShopOrder::where('promocode_id', $promocode->id)->where('order_status', '!=', 'cancelled')->whereBetween('order_date', [$request->from, $request->to])
            ->get();
        $restaurantOrders = RestaurantOrder::where('promocode_id', $promocode->id)->whereBetween('order_date', [$request->from, $request->to])->where('order_status', '!=', 'cancelled')
            ->get();
        $orderList = collect($shopOrders)->merge($restaurantOrders)->groupBy('customer_id');
        return $this->generatePormocodeReport($orderList);
    }

    private function generatePormocodeReport($orderList)
    {
        $data = [];
        $totalAmountSum = 0;
        $totalPromoDiscountSum = 0;
        $totalFrequency = 0;

        foreach ($orderList as $orders) {
            $totalAmount=0;
            $totalPromoDiscount=0;
            foreach ($orders as $order) {
                $totalAmount += ($order->tax+$order->amount);
                $totalPromoDiscount += $order->promocode_amount ? $order->promocode_amount : 0;
            }
            $customer = Customer::where('id', $orders[0]->customer_id)->first();
            $totalAmountSum += $totalAmount;
            $totalPromoDiscountSum += $totalPromoDiscount;
            $totalFrequency+=$orders->count();
            $data[] = [
                'frequency' =>$orders->count(),
                'slug'=>$customer->slug,
                'name'=>$customer->name,
                'email'=>$customer->email,
                'phone_number'=>$customer->phone_number,
                'total_amount'=>$totalAmount,
                'promo_discount'=>$totalPromoDiscount,
            ];
        }

        $result = [
            'total_promo_discount_sum' => $totalPromoDiscountSum,
            'total_amount_sum' => $totalAmountSum,
            'total_frequency' => $totalFrequency,
            'total_user_count' => $orderList->count(),
            'customers' => $data,
        ];

        return $result;
    }

    private function generateReport($orderList)
    {
        $data = [];
        $totalAmountSum = 0;
        $totalPromoDiscount = 0;

        foreach ($orderList as $order) {
            $totalAmount = $order->order_status == 'cancelled' ? 0 : ($order->tax+$order->amount);
            $totalAmountSum += $totalAmount;
            $totalPromoDiscount += $order->order_status == 'cancelled' && $order->promocode_amount ? $order->promocode_amount : 0;

            $data[] = [
                'order_no' => $order->order_no,
                'invoice_no' => $order->invoice_no,
                'order_date' => Carbon::parse($order->order_date)->format('M d Y h:i a'),
                'invoice_date' =>  $order->invoice_date ? Carbon::parse($order->invoice_date)->format('M d Y h:i a') : null,
                'promo_discount' => $order->order_status != 'cancelled' && $order->promocode_amount ? $order->promocode_amount : '0',
                'total_amount' => $totalAmount,
                'payment_mode' => $order->payment_mode,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
            ];
        }

        $result = [
            'total_promo_discount' => $totalPromoDiscount,
            'total_amount_sum' => $totalAmountSum,
            'invoice' => $data,
        ];

        return $result;
    }
}
