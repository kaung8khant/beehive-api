<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    public function getShopOrderCommissions(Request $request)
    {
        // $result = ShopOrderItem::with('shop', 'vendor.shopOrder')
        //     ->where(function ($query) use ($request) {
        //         $query->whereHas('vendor.shopOrder', function ($query) use ($request) {
        //             $query->whereBetween('order_date', array($request->from, $request->to))
        //                 ->where('order_status', 'delivered');
        //         });
        //     })
        //     ->where('commission', '>', 0)->get();
        // return response()->json($result);

        $result= DB::table('shop_orders as so')
        ->join('shop_order_vendors as sov', 'sov.shop_order_id', '=', 'so.id')
        ->join('shop_order_items as soi', 'soi.shop_order_vendor_id', '=', 'sov.id')
        ->join('shops as s', 's.id', '=', 'sov.shop_id')
        ->where('soi.commission', '>', 0)
        ->where('so.order_status', 'delivered')
        ->whereBetween('so.order_date', array($request->from, $request->to))
        ->select('s.id', DB::raw('count(*) as order_quantity'), DB::raw('SUM(soi.commission) as commission_total'))
        ->groupBy('s.id')
        ->get()
        ->map(function ($data) use ($request) {
            $shopCommissions = DB::table('shops')->select('slug', 'name')->where('id', $data->id)->first();
            $shopCommissions->commission_total = $data->commission_total;
            $shopCommissions->order_quantity = $data->order_quantity;
            $shopOrders = DB::table('shop_orders as so')->join('shop_order_vendors as sov', 'sov.shop_order_id', '=', 'so.id')->where('sov.shop_id', $data->id)
            ->where('so.commission', '>', 0)
            ->where('so.order_status', 'delivered')
            ->whereBetween('so.order_date', array($request->from, $request->to))
            ->select('so.slug', 'so.id')->get()
            ->map(function ($data) {
                $orders=$data;
                $orders->invoice_id=sprintf('%08d', $data->id);
                return $orders;
            });
            $shopCommissions->orders= $shopOrders;
            return  $shopCommissions;
        });

        return response()->json($result);
    }

    public function getOneShopOrderCommissions(Request $request, Shop $shop)
    {
        // $result = ShopOrderItem::with('shop', 'product')
        //     ->where(function ($query) use ($request, $shop) {
        //         $query->whereHas('shop', function ($query) use ($shop) {
        //             $query->where('slug', $shop->slug);
        //         })
        //             ->whereHas('vendor.shopOrder', function ($query) use ($request) {
        //                 $query->whereBetween('order_date', array($request->from, $request->to))
        //                     ->where('order_status', 'delivered');
        //             });
        //     })
        //     ->where('commission', '>', 0)->get();
        // return response()->json($result);

        $result= DB::table('shop_orders as so')
        ->join('shop_order_vendors as sov', 'sov.shop_order_id', '=', 'so.id')
        ->join('shop_order_items as soi', 'soi.shop_order_vendor_id', '=', 'sov.id')
        ->where('sov.shop_id', $shop->id)
        ->where('soi.commission', '>', 0)
        // ->where('so.order_status', 'delivered')
        ->whereBetween('so.order_date', array($request->from, $request->to))
        ->select('soi.product_name', 'soi.product_id', 'soi.vendor_price', 'soi.discount', 'soi.amount', 'soi.variant', DB::raw('SUM(soi.quantity) as quantity'), DB::raw('SUM(soi.commission) as commission'))
        ->groupBy(['soi.product_name', 'soi.product_id','soi.variant', 'soi.vendor_price','soi.discount','soi.amount'])
        ->get()
        ->map(function ($data) {
            $shopCommission=$data;
            $shopCommission->variant=json_decode($data->variant);
            return $shopCommission;
        });

        return response()->json($result);
    }


    public function getRestaurantOrderCommissions(Request $request)
    {
        // $result = RestaurantOrder::where('commission', '>', 0)
        //     ->where('order_status', 'delivered')
        //     ->whereBetween('order_date', array($request->from, $request->to))
        //     ->get();

        // return response()->json($result);

        $result= DB::table('restaurant_orders as ro')
        ->join('restaurants as r', 'r.id', '=', 'ro.restaurant_id')
        ->where('ro.commission', '>', 0)
        ->where('ro.order_status', 'delivered')
        ->whereBetween('ro.order_date', array($request->from, $request->to))
        ->select('r.id', DB::raw('SUM(ro.commission) as commission_total'), DB::raw('count(*) as order_quantity'))
        ->groupBy('r.id')
        ->get()
        ->map(function ($data) use ($request) {
            $restaurantCommissions = DB::table('restaurants')->select('slug', 'name', 'commission as commission_percentage')->where('id', $data->id)->first();
            $restaurantCommissions->commission_total = $data->commission_total;
            $restaurantCommissions->order_quantity = $data->order_quantity;
            $restaurantOrders = DB::table('restaurant_orders as ro')->select('slug', 'id')->where('restaurant_id', $data->id)
            ->where('ro.commission', '>', 0)
            ->where('ro.order_status', 'delivered')
            ->whereBetween('ro.order_date', array($request->from, $request->to))->get()
            ->map(function ($data) {
                $orders=$data;
                $orders->invoice_id=sprintf('%08d', $data->id);
                return  $orders;
            });


            $restaurantCommissions->orders=$restaurantOrders;
            return  $restaurantCommissions;
        });
        return response()->json($result);
    }


    public function getRestaurantBranchOrderCommissions(Request $request, RestaurantBranch $restaurantBranch)
    {
        // $result = RestaurantOrder::whereHas('restaurantBranch', function ($query) use ($restaurantBranch) {
        //     $query->where('slug', $restaurantBranch->slug);
        // })
        //     ->where('commission', '>', 0)
        //     ->where('order_status', 'delivered')
        //     ->whereBetween('order_date', array($request->from, $request->to))
        //     ->get();

        // return response()->json($result);


        $result= DB::table('restaurant_orders as ro')
                ->join('restaurants as r', 'r.id', '=', 'ro.restaurant_id')
        ->where('ro.restaurant_branch_id', $restaurantBranch->id)
        ->where('ro.commission', '>', 0)
        ->where('ro.order_status', 'delivered')
        ->whereBetween('ro.order_date', array($request->from, $request->to))
        ->select('r.commission as commission_percentage', 'ro.commission as commission_total', 'ro.id', 'ro.slug')
        ->get()
        ->map(function ($data) use ($restaurantBranch) {
            $subTotal = DB::table('restaurant_order_items')
            ->where('restaurant_id', $restaurantBranch->restaurant_id)
            ->sum(DB::raw('(amount - discount) * quantity'));

            $restaurantCommissions=$data;
            $restaurantCommissions->invoice_id=sprintf('%08d', $data->id);
            $restaurantCommissions->subtotal=$subTotal;

            return  $restaurantCommissions;
        });

        return response()->json($result);
    }

    public function getOneRestaurantOrderCommissions(Request $request, Restaurant $restaurant)
    {
        // $result = RestaurantOrder::whereHas('restaurant', function ($query) use ($restaurant) {
        //     $query->where('slug', $restaurant->slug);
        // })
        //     ->where('commission', '>', 0)
        //     ->where('order_status', 'delivered')
        //     ->whereBetween('order_date', array($request->from, $request->to))
        //     ->get();

        // return response()->json($result);

        $result= DB::table('restaurant_orders as ro')
                ->join('restaurant_branches as rb', 'rb.id', '=', 'ro.restaurant_branch_id')
        ->where('ro.restaurant_id', $restaurant->id)
        ->where('ro.commission', '>', 0)
        ->where('ro.order_status', 'delivered')
        ->whereBetween('ro.order_date', array($request->from, $request->to))
        ->select('rb.name as restaurant_branch_name', 'rb.slug as restaurant_branch_slug', 'ro.commission as commission_total', 'ro.id', 'ro.slug')
        ->get()
        ->map(function ($data) use ($restaurant) {
            $subTotal = DB::table('restaurant_order_items')
            ->where('restaurant_id', $restaurant->id)
            ->sum(DB::raw('(amount - discount) * quantity'));

            $restaurantCommissions=$data;
            $restaurantCommissions->invoice_id=sprintf('%08d', $data->id);
            $restaurantCommissions->subtotal=$subTotal;

            return  $restaurantCommissions;
        });

        return response()->json($result);
    }
}
