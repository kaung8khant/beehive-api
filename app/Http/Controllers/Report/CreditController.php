<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function getCreditUsedCustomerOrderInvoiceReport(Request $request, Customer $customer)
    {
        $shopOrders = ShopOrder::where('customer_id', $customer->id)
            ->where('payment_mode', 'Credit')
            ->whereBetween('order_date', [$request->from, $request->to])
            ->get();
        $restaurantOrders = RestaurantOrder::where('customer_id', $customer->id)->where('payment_mode', 'Credit')
            ->whereBetween('order_date', [$request->from, $request->to])
            ->get();
        $orders = collect($shopOrders)->merge($restaurantOrders);

        return $this->generateReport($orders);
    }

    private function generateReport($restaurantOrders)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;

        foreach ($restaurantOrders as $order) {
            $amount = $order->order_status == 'cancelled' ? 0 : $order->amount;
            $totalAmount = $order->order_status == 'cancelled' ? 0 : $order->total_amount;

            $amountSum += $amount;
            $totalAmountSum += $totalAmount;

            $data[] = [
                'order_no' => $order->order_no,
                'invoice_no' => $order->invoice_no,
                'order_date' => Carbon::parse($order->order_date)->format('M d Y h:i a'),
                'invoice_date' =>$order->invoice_date? Carbon::parse($order->invoice_date)->format('M d Y h:i a') : null,
                'revenue' => $amount,
                'commercial_tax' => $order->order_status != 'cancelled' && $order->tax ? $order->tax : 0,
                'discount' => $order->order_status != 'cancelled' && $order->discount ? $order->discount : 0,
                'promo_discount' => $order->order_status != 'cancelled' && $order->promocode_amount ? $order->promocode_amount : 0,
                'total_amount' => $totalAmount,
                'order_status' => $order->order_status,
                'order_type' => $order->order_type,
                'special_instructions' => $order->special_instruction,
            ];
        }

        $result = [
            'revenue_sum' => $amountSum,
            'total_amount_sum' => $totalAmountSum,
            'invoice' => $data,
        ];

        return $result;
    }
}
