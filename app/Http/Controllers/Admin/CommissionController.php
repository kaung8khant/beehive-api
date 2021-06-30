<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\Shop;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderVendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    // public function getShopOrderCommissions(Request $request)
    // {
    //     $startDate=null;
    //     $endDate=null;
    //     if ($request->type === 'today') {
    //         $startDate = Carbon::now()->startOfDay();
    //         $endDate = Carbon::now();
    //     } elseif ($request->type === 'yesterday') {
    //         $startDate = Carbon::now()->subDays(1)->startOfDay();
    //         $endDate = Carbon::now()->subDays(1);
    //     } elseif ($request->type === 'thisweek') {
    //         $startDate = Carbon::now()->subDays(6);
    //         $endDate = Carbon::now();
    //     } elseif ($request->type === 'thismonth') {
    //         $startDate = Carbon::now()->subDays(29);
    //         $endDate = Carbon::now();
    //     } elseif ($request->type === 'thisyear') {
    //         $startDate = Carbon::now()->subMonths(11)->startOfMonth();
    //         $endDate = Carbon::now();
    //     }
    //     $result= ShopOrderItem::with('shop', 'vendor', 'product')
    //     ->where('commission', '>', 0)
    //     ->whereBetween('created_at', array($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d') . ' 23:59:59'))->get();

    //     return response()->json($result);
    // }

    // $startDate=null;
    // $endDate=null;
    // if ($request->type === 'today') {
    //     $startDate = Carbon::now()->startOfDay();
    //     $endDate = Carbon::now();
    // } elseif ($request->type === 'yesterday') {
    //     $startDate = Carbon::now()->subDays(1)->startOfDay();
    //     $endDate = Carbon::now()->subDays(1);
    // } elseif ($request->type === 'thisweek') {
    //     // $startDate = Carbon::now()->subDays(6)->startOfDay();
    //     $startDate = Carbon::now()->startOfWeek();
    //     $endDate = Carbon::now()->endOfWeek();
    // } elseif ($request->type === 'thismonth') {
    //     $startDate = Carbon::now()->startOfMonth();
    //     $endDate = Carbon::now()->endOfMonth();
    // } elseif ($request->type === 'lastmonth') {
    //     $startDate = Carbon::now()->subMonth()->startOfMonth();
    //     $endDate = Carbon::now()->subMonth()->endOfMonth();
    // } elseif ($request->type === 'thisyear') {
    //     $startDate = Carbon::now()->startOfYear();
    //     $endDate = Carbon::now()->endOfYear();
    // }

    public function getShopOrderCommissions(Request $request)
    {
        // $result = ShopOrderVendor::with('shopOrder', 'shop')
        //     ->whereHas('shopOrder', function ($query) use ($request) {
        //         $query->where('commission', '>', 0)
        //             ->whereBetween('order_date', array($request->from, $request->to));
        //     })->get();

        // return response()->json($result);

        $result = ShopOrderItem::with('shop', 'vendor.shopOrder')
            ->where(function ($query) use ($request) {
                $query->whereHas('vendor.shopOrder', function ($query) use ($request) {
                    $query->whereBetween('order_date', array($request->from, $request->to));
                });
            })
            ->where('commission', '>', 0)->get();
        return response()->json($result);
    }



    public function getOneShopOrderCommissions(Request $request, Shop $shop)
    {
        $result = ShopOrderItem::with('shop', 'product')
            ->where(function ($query) use ($request, $shop) {
                $query->whereHas('shop', function ($query) use ($shop) {
                    $query->where('slug', $shop->slug);
                })
                    ->whereHas('vendor.shopOrder', function ($query) use ($request) {
                        $query->whereBetween('order_date', array($request->from, $request->to));
                    });
            })
            ->where('commission', '>', 0)->get();
        return response()->json($result);
    }


    public function getRestaurantOrderCommissions(Request $request)
    {
        // $order = DB::table('restaurant_orders')
        // ->join('restaurants', 'restaurants.id', '=', 'restaurant_orders.restaurant_id')
        // ->where('restaurant_orders.commission', '>', 0)
        // ->whereBetween('order_date', array($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d') . ' 23:59:59'))
        // ->select('restaurants.name', 'restaurant_orders.commission', 'restaurant_orders.slug', 'restaurant_orders.id', 'restaurants.slug as restaurant_slug')
        // ->get()
        // ->groupBy('restaurant_slug')
        // ->toArray();

        // dd($order);

        // foreach ($order as $key => $value) {
        //     foreach ($value as $key => $v) {
        //         dd($value[$key]->commission);
        //     }
        // }

        $result = RestaurantOrder::where('commission', '>', 0)
            ->whereBetween('order_date', array($request->from, $request->to))
            ->get();

        return response()->json($result);
    }


    public function getRestaurantBranchOrderCommissions(Request $request, RestaurantBranch $restaurantBranch)
    {
        $result = RestaurantOrder::whereHas('restaurantBranch', function ($query) use ($restaurantBranch) {
            $query->where('slug', $restaurantBranch->slug);
        })
            ->where('commission', '>', 0)
            ->whereBetween('order_date', array($request->from, $request->to))
            ->get();

        return response()->json($result);
    }

    public function getOneRestaurantOrderCommissions(Request $request, Restaurant $restaurant)
    {
        $result = RestaurantOrder::whereHas('restaurant', function ($query) use ($restaurant) {
            $query->where('slug', $restaurant->slug);
        })
            ->where('commission', '>', 0)
            ->whereBetween('order_date', array($request->from, $request->to))
            ->get();

        return response()->json($result);
    }
}
