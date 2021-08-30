<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderVendor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShopOrderController extends Controller
{
    public function getShopSaleInvoiceReport(Request $request)
    {
        $shopOrders = ShopOrder::with('contact')
            ->whereBetween('order_date', [$request->from, $request->to])
            ->orderBy('id')
            ->get();

        return $this->generateReport($shopOrders);
    }

    public function getProductSaleReport(Request $request)
    {
        $shopOrderItems = ShopOrderItem::orderBy('id')
            ->whereHas('vendor.shopOrder', function ($query) use ($request) {
                $query->whereBetween('order_date', array($request->from, $request->to));
            })
            ->get();
        return $this->generateProductSaleReport($shopOrderItems);
    }


    private function generateReport($shopOrders)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;

        foreach ($shopOrders as $order) {
            $amount = $order->order_status == 'cancelled' ? 0 : $order->amount;
            $commission =  $order->commission;
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
                'customer_name' => $order->contact->customer_name,
                'phone_number' => $order->contact->phone_number,
                'revenue' => $amount,
                'commercial_tax' => $order->order_status != 'cancelled' && $order->tax ? $order->tax : 0,
                'discount' => $order->order_status != 'cancelled' && $order->discount ? $order->discount : 0,
                'promo_discount' => $order->order_status != 'cancelled' && $order->promocode_amount ? $order->promocode_amount : 0,
                'total_amount' => $totalAmount,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
                'payment_mode' => $order->payment_mode,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'special_instructions' => $order->special_instruction,
            ];
        }

        $result = [
            'revenue_sum' => $amountSum,
            'total_amount_sum' => $totalAmountSum,
            'commission_sum' => $commissionSum,
            'commission_ct_sum' => $commissionCtSum,
            'balance_sum' => $balanceSum,
            'invoice' => $data,
        ];

        return $result;
    }

    private function generateProductSaleReport($shopOrderItems)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;

        foreach ($shopOrderItems as $item) {
            $shop = Shop::where('id', $item->shop_id)->first();

            $amount = $item->vendor->shopOrder->order_status == 'cancelled' ? 0 : ($item->amount * $item->quantity);
            $commission =  $item->commission;
            $commissionCt = $commission * 0.05;
            $totalAmount = $item->vendor->shopOrder->order_status == 'cancelled' ? 0 : $item->total_amount;
            $balance = $totalAmount - $commissionCt;

            $amountSum += $amount;
            $totalAmountSum += $totalAmount;
            $commissionSum += $commission;
            $commissionCtSum += $commissionCt;
            $balanceSum += $balance;

            $data[] = [
                'invoice_id' => $item->vendor->shopOrder->invoice_id,
                'order_date' => Carbon::parse($item->vendor->shopOrder->order_date)->format('M d Y h:i a'),
                'customer_name' => $item->vendor->shopOrder->contact->customer_name,
                'phone_number' => $item->vendor->shopOrder->contact->phone_number,
                'product_name' => $item->product_name,
                'price' => $item->amount,
                'vendor_price' => $item->vendor_price,
                'variant' => $item->variant,
                'quantity' => $item->quantity,
                'shop' => $shop->name,
                'revenue' => $amount,
                'commercial_tax' => $item->vendor->shopOrder->order_status != 'cancelled' && $item->tax ? $item->tax * $item->quantity : 0,
                'discount' => $item->vendor->shopOrder->order_status != 'cancelled' && $item->discount ? $item->discount * $item->quantity : 0,
                'total_amount' => $totalAmount,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
                'payment_mode' => $item->vendor->shopOrder->payment_mode,
                'payment_status' => $item->vendor->shopOrder->payment_status,
                'order_status' => $item->vendor->shopOrder->order_status,
                'special_instructions' => $item->vendor->shopOrder->special_instruction,
            ];
        }

        $result = [
            'revenue_sum' => $amountSum,
            'total_amount_sum' => $totalAmountSum,
            'commission_sum' => $commissionSum,
            'commission_ct_sum' => $commissionCtSum,
            'balance_sum' => $balanceSum,
            'invoice' => $data,
        ];

        return $result;
    }
}
