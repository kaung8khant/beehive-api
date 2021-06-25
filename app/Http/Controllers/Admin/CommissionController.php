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

    public function getShopOrderCommissions(Request $request)
    {
        $startDate=null;
        $endDate=null;
        if ($request->type === 'today') {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now();
        } elseif ($request->type === 'yesterday') {
            $startDate = Carbon::now()->subDays(1)->startOfDay();
            $endDate = Carbon::now()->subDays(1);
        } elseif ($request->type === 'thisweek') {
            // $startDate = Carbon::now()->subDays(6)->startOfDay();
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now();
        } elseif ($request->type === 'thismonth') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now();
        } elseif ($request->type === 'lastmonth') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($request->type === 'thisyear') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now();
        }
        $result= ShopOrderVendor::with('shopOrder', 'shop')
        ->whereHas('shopOrder', function ($query) {
            $query->where('commission', '>', 0);
        })
        ->whereBetween('created_at', array($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d') . ' 23:59:59'))->get();

        return response()->json($result);
    }



    public function getOneShopOrderCommissions(Request $request, Shop $shop)
    {
        $startDate=null;
        $endDate=null;
        if ($request->type === 'today') {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now();
        } elseif ($request->type === 'yesterday') {
            $startDate = Carbon::now()->subDays(1)->startOfDay();
            $endDate = Carbon::now()->subDays(1);
        } elseif ($request->type === 'thisweek') {
            // $startDate = Carbon::now()->subDays(6)->startOfDay();
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now();
        } elseif ($request->type === 'thismonth') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now();
        } elseif ($request->type === 'lastmonth') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($request->type === 'thisyear') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now();
        }
        $result= ShopOrderItem::with('shop', 'product')->whereHas('shop', function ($query) use ($shop) {
            $query->where('slug', $shop->slug);
        })
        ->where('commission', '>', 0)
        ->whereBetween('created_at', array($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d') . ' 23:59:59'))->get();

        return response()->json($result);
    }


    public function getRestaurantOrderCommissions(Request $request)
    {
        $startDate=null;
        $endDate=null;
        if ($request->type === 'today') {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now();
        } elseif ($request->type === 'yesterday') {
            $startDate = Carbon::now()->subDays(1)->startOfDay();
            $endDate = Carbon::now()->subDays(1);
        } elseif ($request->type === 'thisweek') {
            // $startDate = Carbon::now()->subDays(6)->startOfDay();
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now();
        } elseif ($request->type === 'thismonth') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now();
        } elseif ($request->type === 'lastmonth') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($request->type === 'thisyear') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now();
        }
        $result= RestaurantOrder::where('commission', '>', 0)
            ->whereBetween('created_at', array($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d') . ' 23:59:59'))
            ->get();

        return response()->json($result);
    }


    public function getRestaurantBranchOrderCommissions(Request $request, RestaurantBranch $restaurantBranch)
    {
        $startDate=null;
        $endDate=null;
        if ($request->type === 'today') {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now();
        } elseif ($request->type === 'yesterday') {
            $startDate = Carbon::now()->subDays(1)->startOfDay();
            $endDate = Carbon::now()->subDays(1);
        } elseif ($request->type === 'thisweek') {
            // $startDate = Carbon::now()->subDays(6)->startOfDay();
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now();
        } elseif ($request->type === 'thismonth') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now();
        } elseif ($request->type === 'lastmonth') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($request->type === 'thisyear') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now();
        }
        $result= RestaurantOrder::whereHas('restaurantBranch', function ($query) use ($restaurantBranch) {
            $query->where('slug', $restaurantBranch->slug);
        })
            ->where('commission', '>', 0)
            ->whereBetween('created_at', array($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d') . ' 23:59:59'))
            ->get();

        return response()->json($result);
    }

    public function getOneRestaurantOrderCommissions(Request $request, Restaurant $restaurant)
    {
        $startDate=null;
        $endDate=null;
        if ($request->type === 'today') {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now();
        } elseif ($request->type === 'yesterday') {
            $startDate = Carbon::now()->subDays(1)->startOfDay();
            $endDate = Carbon::now()->subDays(1);
        } elseif ($request->type === 'thisweek') {
            // $startDate = Carbon::now()->subDays(6)->startOfDay();
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now();
        } elseif ($request->type === 'thismonth') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now();
        } elseif ($request->type === 'lastmonth') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($request->type === 'thisyear') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now();
        }
        $result= RestaurantOrder::whereHas('restaurant', function ($query) use ($restaurant) {
            $query->where('slug', $restaurant->slug);
        })
            ->where('commission', '>', 0)
            ->whereBetween('created_at', array($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d') . ' 23:59:59'))
            ->get();

        return response()->json($result);
    }
}
