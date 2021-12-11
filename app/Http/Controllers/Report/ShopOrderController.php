<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Product;
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
        $shopOrders = ShopOrder::with('contact', 'vendors')
            ->whereBetween('order_date', [$request->from, $request->to])
            ->orderBy('id');
        if ($request->filterBy === 'orderDate') {
            $shopOrders = $shopOrders->whereBetween('order_date', [$request->from, $request->to])->get();
        } elseif ($request->filterBy === 'deliveredDate') {
            $shopOrders =  $shopOrders
                ->whereHas('vendors', function ($query) use ($request) {
                    $query->whereHas('shopOrderStatuses', function ($q) use ($request) {
                        $q->whereBetween('created_at', [$request->from, $request->to])->where('status', '=', 'delivered')->latest();
                    });
                })
                ->get();
        } else {
            $shopOrders =  $shopOrders
                ->whereHas('vendors', function ($query) use ($request) {
                    $query->whereHas('shopOrderStatuses', function ($q) use ($request) {
                        $q->whereBetween('created_at', [$request->from, $request->to])->where('status', '=', 'pickUp')->latest();
                    });
                })
                ->get();
        }

        return $this->generateReport($shopOrders, $request->from, $request->to, $request->filterBy);
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

        // $groups = collect($shopOrderItems)->groupBy(function ($item, $key) {
        //     return $item->product_id . '-' . implode('-', array_map(function ($n) {
        //         return $n['value'];
        //     }, $item->variant)) . '-' . $item->amount . '-' . $item->vendor_price . '-' . $item->discount;
        // });

        $shopOrders = ShopOrder::whereBetween('order_date', [$request->from, $request->to])
            ->where('order_status', '!=', 'cancelled')
            ->get();

        $promoDiscount = 0;
        foreach ($shopOrders as $k => $order) {
            $promoDiscount += $order->promocode_amount;
        }

        return $this->generateProductSaleReport($shopOrderItems, $promoDiscount);
    }

    public function getShopProductSaleReport(Request $request, Shop $shop)
    {
        $shopOrderItems = ShopOrderItem::whereHas('vendor.shopOrder', function ($query) use ($request) {
            $query->whereBetween('order_date', array($request->from, $request->to));
        })->where('shop_id', $shop->id)->get();

        // $groups = collect($shopOrderItems)->groupBy(function ($item, $key) {
        //     return $item->product_id . '-' . implode('-', array_map(function ($n) {
        //         return $n['value'];
        //     }, $item->variant)) . '-' . $item->amount . '-' . $item->vendor_price . '-' . $item->discount;
        // });
        return $this->generateProductSaleReport($shopOrderItems);
    }


    public function getShopCategorySaleReport(Request $request)
    {
        $shopOrderItems = ShopOrderItem::with('product')->whereHas('vendor.shopOrder', function ($query) use ($request) {
            $query->whereBetween('order_date', array($request->from, $request->to))->where('order_status', '!=', 'cancelled');
        })->get();

        $groups = collect($shopOrderItems)->groupBy(function ($item, $key) {
            return $item->product->shopCategory->id;
        });
        return $this->generateGroupSaleReport($groups);
    }

    private function generateReport($shopOrders, $from = null, $to = null, $filterBy = null)
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
            $vendorIds = ShopOrderVendor::whereHas('shopOrder', function ($query) use ($order) {
                $query->where('shop_order_id', $order->id);
            })->pluck('id')->toArray();

            $orderStatus = ShopOrderStatus::where('status', 'delivered')
                ->whereHas('vendor', function ($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })->latest('created_at')->first();
            $invoiceOrderStatus = ShopOrderStatus::where('status', 'pickUp')
                ->whereHas('vendor', function ($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })->latest('created_at')->first();

            if ($filterBy === 'deliveredDate') {
                $orderStatus = ShopOrderStatus::where('status', 'delivered')->whereBetween('created_at', array($from, $to))->whereHas('vendor', function ($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })->latest('created_at')->first();
            }
            if ($filterBy === 'invoiceDate') {
                $invoiceOrderStatus = ShopOrderStatus::where('status', 'pickUp')->whereBetween('created_at', array($from, $to))->whereHas('vendor', function ($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })->latest('created_at')->first();
            }

            $data[] = [
                'order_no' => $order->order_no,
                'invoice_no' => $order->invoice_no,
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
                'payment_reference' => $order->payment_reference,
                'order_status' => $order->order_status,
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

    private function generateProductSaleReport($shopOrderItems, $promoDiscount = null)
    {
        $data = [];
        $amountSum = 0;
        $totalAmountSum = 0;
        $commissionSum = 0;
        $commissionCtSum = 0;
        $balanceSum = 0;
        foreach ($shopOrderItems as $key => $item) {
            $amount = $item->vendor->shopOrder->order_status == 'cancelled' ? 0 : ($item->amount * $item->quantity);
            $commission = $item->vendor->shopOrder->order_status == 'cancelled' ? 0  : $item->commission;
            $commissionCt = $commission * 0.05;
            $totalAmount =  $item->vendor->shopOrder->order_status == 'cancelled' ? 0  : $item->total_amount;
            $balance = $totalAmount - $commissionCt;
            $commercialTax = $item->vendor->shopOrder->order_status != 'cancelled' &&  $item->tax ? $item->tax * $item->quantity : 0;
            $discount = $item->vendor->shopOrder->order_status != 'cancelled' &&  $item->discount ? $item->discount * $item->quantity : 0;
            $quantity = $item->vendor->shopOrder->order_status == 'cancelled' ? 0 : $item->quantity;

            $amountSum += $amount;
            $totalAmountSum += $totalAmount;
            $commissionSum += $commission;
            $commissionCtSum += $commissionCt;
            $balanceSum += $balance;

            $shop = Shop::where('id', $item->shop_id)->first();
            $product = Product::where('id', $item->product_id)->first();

            $data[] = [
                'order_no' =>  $item->vendor->shopOrder->order_no,
                'invoice_no' =>  $item->vendor->shopOrder->invoice_no,
                'order_date' => Carbon::parse($item->vendor->shopOrder->order_date)->format('M d Y h:i a'),
                'invoice_date' =>$item->vendor->shopOrder->invoice_date? Carbon::parse($item->vendor->shopOrder->invoice_date)->format('M d Y h:i a') :null,
                'code' => $product->code,
                'product_name' => $item->product_name,
                'price' => $item->amount,
                'shop' => $shop->name,
                'vendor_price' => $item->vendor_price,
                'variant' => $item->variant,
                'quantity' => $quantity,
                'revenue' => $amount,
                'commercial_tax' => $commercialTax ? $commercialTax : 0,
                'discount' => $discount ? $discount : 0,
                'total_amount' => $totalAmount,
                'commission' => $commission ? $commission : 0,
                'commission_ct' => $commissionCt ? $commissionCt : 0,
                'balance' => round($balance),
                'payment_mode' => $item->vendor->shopOrder->payment_mode,
                'payment_status' => $item->vendor->shopOrder->payment_status,
                'payment_reference' => $item->vendor->shopOrder->payment_reference,
                'order_status' => $item->vendor->shopOrder->order_status,
                'special_instructions' => $item->vendor->shopOrder->special_instruction,
                'customer_name' =>  $item->vendor->shopOrder->contact->customer_name,
                'phone_number' => $item->vendor->shopOrder->contact->phone_number,
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

    private function generateGroupSaleReport($groups)
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
            $totalAmount = 0;
            $commission = 0;
            $commissionCt = 0;
            $quantity = 0;

            foreach ($group as $k => $item) {
                $amount += ($item->amount * $item->quantity);
                $commission += $item->commission;
                $totalAmount += $item->total_amount;
                $commercialTax += $item->tax ? $item->tax * $item->quantity : 0;
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
                'name' => $group[0]->product->shopCategory->name,
                'quantity' => $quantity,
                'revenue' => $amount,
                'commercial_tax' => $commercialTax ? $commercialTax : 0,
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
