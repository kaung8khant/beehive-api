<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderStatus;
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

    public function getShopSaleReport(Request $request)
    {
        $shopOrderVendors = ShopOrderVendor::whereHas('shopOrder', function ($query) use ($request) {
            $query->whereBetween('order_date', array($request->from, $request->to))->where('order_status', '!=', 'cancelled');
        })->get()->groupBy('shop_id');
        $shopOrders = ShopOrder::whereBetween('order_date', [$request->from, $request->to])
            ->where('order_status', '!=', 'cancelled')
            ->get();

        $promoDiscount = 0;
        foreach ($shopOrders as $k => $order) {
            $promoDiscount += $order->promocode_amount;
        }
        return $this->generateShopSaleReport($shopOrderVendors, $promoDiscount);
    }

    public function getProductSaleReport(Request $request)
    {
        $shopOrderItems = ShopOrderItem::whereHas('vendor.shopOrder', function ($query) use ($request) {
            $query->whereBetween('order_date', array($request->from, $request->to))->where('order_status', '!=', 'cancelled');
        })->get();

        $groups = collect($shopOrderItems)->groupBy(function ($item, $key) {
            return $item->product_id . '-' . implode('-', array_map(function ($n) {
                return $n['value'];
            }, $item->variant)) . '-' . $item->amount . '-' . $item->vendor_price . '-' . $item->discount;
        });

        $shopOrders = ShopOrder::whereBetween('order_date', [$request->from, $request->to])
            ->where('order_status', '!=', 'cancelled')
            ->get();

        $promoDiscount = 0;
        foreach ($shopOrders as $k => $order) {
            $promoDiscount += $order->promocode_amount;
        }

        return $this->generateProductSaleReport($groups, $promoDiscount);
    }

    public function getShopProductSaleReport(Request $request, Shop $shop)
    {
        $shopOrderItems = ShopOrderItem::whereHas('vendor.shopOrder', function ($query) use ($request) {
            $query->whereBetween('order_date', array($request->from, $request->to))->where('order_status', '!=', 'cancelled');
        })->where('shop_id', $shop->id)->get();

        $groups = collect($shopOrderItems)->groupBy(function ($item, $key) {
            return $item->product_id . '-' . implode('-', array_map(function ($n) {
                return $n['value'];
            }, $item->variant)) . '-' . $item->amount . '-' . $item->vendor_price . '-' . $item->discount;
        });
        return $this->generateShopProductSaleReport($groups);
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
            $commission = $order->order_status == 'cancelled' ? 0 : $order->commission;
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

    private function generateShopSaleReport($shopOrderVendors, $promoDiscount)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;

        foreach ($shopOrderVendors as $group) {
            foreach ($group as $vendor) {
                $shop = Shop::where('id', $vendor->shop_id)->first();

                $amount = $vendor->shopOrder->order_status == 'cancelled' ? 0 : $vendor->amount;
                $commission = $vendor->shopOrder->order_status == 'cancelled' ? 0 : $vendor->commission;
                $commissionCt = $commission * 0.05;
                $totalAmount = $vendor->shopOrder->order_status == 'cancelled' ? 0 : $vendor->total_amount;
                $balance = $totalAmount - $commissionCt;

                $amountSum += $amount;
                $totalAmountSum += $totalAmount;
                $commissionSum += $commission;
                $commissionCtSum += $commissionCt;
                $balanceSum += $balance;
            }
            $data[] = [
                'shop' => $shop->name,
                'revenue' => $amount,
                'commercial_tax' => $vendor->shopOrder->order_status != 'cancelled' && $vendor->tax ? $vendor->tax : 0,
                'discount' => $vendor->shopOrder->order_status != 'cancelled' && $vendor->discount ? $vendor->discount : 0,
                'total_amount' => $totalAmount,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
            ];
        }

        $result = [
            'revenue_sum' => $amountSum,
            'total_amount_sum' => $totalAmountSum,
            'commission_sum' => $commissionSum,
            'commission_ct_sum' => $commissionCtSum,
            'balance_sum' => $balanceSum,
            'promo_discount' => $promoDiscount,
            'invoice' => $data,
        ];

        return $result;
    }

    private function generateProductSaleReport($groups, $promoDiscount)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;

        foreach ($groups as $key => $group) {
            $amount = 0;
            $commercialTax = 0;
            $discount = 0;
            $totalAmount = 0;
            $commission = 0;
            $commissionCt = 0;
            $quantity = 0;
            $shop = Shop::where('id', $group[0]->shop_id)->first();

            foreach ($group as $k => $item) {
                $amount += ($item->amount * $item->quantity);
                $commission += $item->commission;
                $totalAmount += $item->total_amount;
                $commercialTax += $item->tax ? $item->tax * $item->quantity : 0;
                $discount += $item->discount ? $item->discount * $item->quantity : 0;
                $quantity += $item->quantity;
            }
            $commissionCt += $commission * 0.05;
            $amountSum += $amount;
            $totalAmountSum += $totalAmount;
            $commissionSum += $commission;
            $commissionCtSum += $commissionCt;
            $balance = $totalAmount - $commissionCt;
            $balanceSum += $balance;

            $data[] = [
                'product_name' => $group[0]->product_name,
                'price' => $group[0]->amount,
                'shop' => $shop->name,
                'vendor_price' => $group[0]->vendor_price,
                'variant' => $group[0]->variant,
                'quantity' => $quantity,
                'revenue' => $amount,
                'commercial_tax' => $commercialTax ? $commercialTax : 0,
                'discount' => $discount ? $discount : 0,
                'total_amount' => $totalAmount,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
            ];
        }

        $result = [
            'revenue_sum' => $amountSum,
            'total_amount_sum' => $totalAmountSum,
            'commission_sum' => $commissionSum,
            'commission_ct_sum' => $commissionCtSum,
            'balance_sum' => $balanceSum,
            'promo_discount' => $promoDiscount,
            'invoice' => $data,
        ];

        return $result;
    }

    private function generateShopProductSaleReport($groups)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;

        foreach ($groups as $key => $group) {
            $amount = 0;
            $commercialTax = 0;
            $discount = 0;
            $totalAmount = 0;
            $commission = 0;
            $commissionCt = 0;
            $quantity = 0;
            $shop = Shop::where('id', $group[0]->shop_id)->first();

            foreach ($group as $k => $item) {
                $amount += ($item->amount * $item->quantity);
                $commission += $item->commission;
                $totalAmount += $item->total_amount;
                $commercialTax += $item->tax ? $item->tax * $item->quantity : 0;
                $discount += $item->discount ? $item->discount * $item->quantity : 0;
                $quantity += $item->quantity;
            }

            $commissionCt += $commission * 0.05;
            $amountSum += $amount;
            $totalAmountSum += $totalAmount;
            $commissionSum += $commission;
            $commissionCtSum += $commissionCt;
            $balance = $totalAmount - $commissionCt;
            $balanceSum += $balance;
            $data[] = [
                'product_name' => $group[0]->product_name,
                'price' => $group[0]->amount,
                'shop' => $shop->name,
                'vendor_price' => $group[0]->vendor_price,
                'variant' => $group[0]->variant,
                'quantity' => $quantity,
                'revenue' => $amount,
                'commercial_tax' => $commercialTax ? $commercialTax : 0,
                'discount' => $discount ? $discount : 0,
                'total_amount' => $totalAmount,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
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
