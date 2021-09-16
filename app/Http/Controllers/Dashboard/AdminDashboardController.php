<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ShopOrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function getCountData()
    {
        $result = [
            'restaurants' => DB::table('restaurants')->where('restaurants.is_enable', 1)->count(),
            'shops' => DB::table('shops')->where('shops.is_enable', 1)->count(),
            'customers' => DB::table('customers')->count(),
            'drivers' => $this->getDriversCount(),
            'promo_codes' => DB::table('promocodes')->count(),
        ];

        return response()->json($result);
    }

    public function getRestaurantOrders(Request $request)
    {
        if ($request->type === 'weekly') {
            $startDate = Carbon::now()->subDays(6);
            $endDate = Carbon::now();
        } elseif ($request->type === 'monthly') {
            $startDate = Carbon::now()->subDays(29);
            $endDate = Carbon::now();
        } else {
            $startDate = Carbon::now()->subMonths(11)->startOfMonth();
            $endDate = Carbon::now();
        }

        $restaurantOrders = DB::table('restaurant_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('order_status', '!=', 'cancelled')->select('restaurant_branch_id', DB::raw('count(*) AS total_orders'))
            ->groupBy('restaurant_branch_id')
            ->orderby('total_orders', 'DESC')->limit(10)->get();

        $data = [];

        foreach ($restaurantOrders as $key) {
            $restaurantBranch = DB::table('restaurant_branches as b')
                ->join('restaurants as r', 'r.id', '=', 'b.restaurant_id')
                ->select('b.name as branch_name', 'r.name as restaurant_name', 'r.id as restaurant_id')
                ->where('b.id', $key->restaurant_branch_id)
                ->first();

            if ($restaurantBranch) {
                $totalAmount = DB::table('restaurant_order_items')
                    ->where('restaurant_id', $restaurantBranch->restaurant_id)
                    ->sum(DB::raw('(amount + tax - discount) * quantity'));

                $branchData = [
                    'restaurant_name' => $restaurantBranch->restaurant_name,
                    'restaurant_branch_name' => $restaurantBranch->branch_name,
                    'total_orders' => $key->total_orders,
                    'total_amount' => $totalAmount,
                ];

                array_push($data, $branchData);
            }
        }

        $result = [
            'data' => $data,
        ];

        return response()->json($result);
    }

    public function getShopOrders(Request $request)
    {
        if ($request->type === 'weekly') {
            $startDate = Carbon::now()->subDays(6);
            $endDate = Carbon::now();
        } elseif ($request->type === 'monthly') {
            $startDate = Carbon::now()->subDays(29);
            $endDate = Carbon::now();
        } else {
            $startDate = Carbon::now()->subMonths(11)->startOfMonth();
            $endDate = Carbon::now();
        }

        $shopOrders = DB::table('shop_order_items as oi')
            ->join('shop_order_vendors as ov', 'ov.id', '=', 'oi.shop_order_vendor_id')
            ->join('shop_orders as o', 'o.id', '=', 'ov.shop_order_id')
            ->where('o.order_status', '!=', 'cancelled')
            ->where('o.order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('o.order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->select('oi.shop_id', DB::raw('count(*) AS total_orders'))
            ->groupBy('oi.shop_id')
            ->orderBy('total_orders', 'DESC')->limit(10)->get();


        $data = [];

        foreach ($shopOrders as $key) {
            $shop = DB::table('shops')->where('id', $key->shop_id)->first();

            if ($shop) {
                $totalAmount = DB::table('shop_order_items')
                    ->where('shop_id', $key->shop_id)
                    ->sum(DB::raw('(amount + tax - discount) * quantity'));

                $shopData = [
                    'shop_name' => $shop->name,
                    'total_orders' => $key->total_orders,
                    'total_amount' => $totalAmount,
                ];

                array_push($data, $shopData);
            }
        }

        $result = [
            'data' => $data,
        ];

        return response()->json($result);
    }

    public function getOrderChartData(Request $request)
    {
        if ($request->type === 'weekly') {
            $result = $this->getWeeklyOrders();
        } elseif ($request->type === 'monthly') {
            $result = $this->getMonthlyOrders();
        } else {
            $result = $this->getYearlyOrders();
        }

        return response()->json($result);
    }

    private function getYearlyOrders()
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now();

        $restaurantOrders = DB::table('restaurant_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('order_status', '!=', 'cancelled')
            ->select(DB::raw('MONTH(order_date) AS month_number'), DB::raw('DATE_FORMAT(order_date, "%b") AS label'), DB::raw('count(*) AS total_orders'))
            ->groupBy('month_number', 'label')
            ->orderBy('month_number')
            ->get();

        $shopOrders = DB::table('shop_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('order_status', '!=', 'cancelled')
            ->select(DB::raw('MONTH(order_date) AS month_number'), DB::raw('DATE_FORMAT(order_date, "%b") AS label'), DB::raw('count(*) AS total_orders'))
            ->groupBy('month_number', 'label')
            ->orderBy('month_number')
            ->get();

        foreach ($restaurantOrders as $order) {
            unset($order->month_number);
        }

        foreach ($shopOrders as $order) {
            unset($order->month_number);
        }

        return [
            'restaurant_orders' => $restaurantOrders,
            'shop_orders' => $shopOrders,
        ];
    }

    private function getMonthlyOrders()
    {
        $startDate = Carbon::now()->subDays(29);
        $endDate = Carbon::now();

        $restaurantOrders = DB::table('restaurant_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('order_status', '!=', 'cancelled')
            ->select(DB::raw('DATE(order_date) AS label'), DB::raw('count(*) AS total_orders'))
            ->groupBy('label')
            ->get();

        $shopOrders = DB::table('shop_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('order_status', '!=', 'cancelled')
            ->select(DB::raw('DATE(order_date) AS label'), DB::raw('count(*) AS total_orders'))
            ->groupBy('label')
            ->get();

        return [
            'restaurant_orders' => $restaurantOrders,
            'shop_orders' => $shopOrders,
        ];
    }

    private function getWeeklyOrders()
    {
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();

        $restaurantOrders = DB::table('restaurant_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('order_status', '!=', 'cancelled')
            ->select(DB::raw('DATE(order_date) AS label'), DB::raw('count(*) AS total_orders'))
            ->groupBy('label')
            ->get();

        $shopOrders = DB::table('shop_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('order_status', '!=', 'cancelled')
            ->select(DB::raw('DATE(order_date) AS label'), DB::raw('count(*) AS total_orders'))
            ->groupBy('label')
            ->get();

        return [
            'restaurant_orders' => $restaurantOrders,
            'shop_orders' => $shopOrders,
        ];
    }

    private function getDriversCount()
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'Driver');
        })->count();
    }

    public function getTopCustomers()
    {
        $restaurantCustomers = DB::table('restaurant_orders as ro')
            ->join('customers as c', 'c.id', '=', 'ro.customer_id')
            ->join('restaurant_order_items as roi', 'roi.restaurant_order_id', '=', 'ro.id')
            ->select('c.id', DB::raw('SUM((amount + tax - discount) * quantity) as total'))
            ->where('ro.order_status', '!=', 'cancelled')->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()
            ->map(function ($data) {
                $customer = DB::table('customers')->select('slug', 'name', 'phone_number')->where('id', $data->id)->first();
                $customer->total = $data->total;
                return $customer;
            });

        $shopCustomers = DB::table('shop_orders as so')
            ->join('customers as c', 'c.id', '=', 'so.customer_id')
            ->join('shop_order_vendors as sov', 'sov.shop_order_id', '=', 'so.id')
            ->join('shop_order_items as soi', 'soi.shop_order_vendor_id', '=', 'sov.id')
            ->select('c.id', DB::raw('SUM((amount + tax - discount) * quantity) as total'))
            ->where('so.order_status', '!=', 'cancelled')->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()
            ->map(function ($data) {
                $customer = DB::table('customers')->select('slug', 'name', 'phone_number')->where('id', $data->id)->first();
                $customer->total = $data->total;
                return $customer;
            });

        return [
            'restaurant_top_customers' => $restaurantCustomers,
            'shop_top_customers' => $shopCustomers,
        ];
    }

    public function getTopShopCategories()
    {
        $shopCategories = DB::table('shop_orders as so')
            ->join('shop_order_vendors as sov', 'sov.shop_order_id', '=', 'so.id')
            ->join('shop_order_items as soi', 'soi.shop_order_vendor_id', '=', 'sov.id')
            ->join('products as p', 'p.id', '=', 'soi.product_id')
            ->join('shop_categories as sc', 'sc.id', '=', 'p.shop_category_id')
            ->select('sc.id',DB::raw('count(*) AS total'))
            ->where('so.order_status', '!=', 'cancelled')->groupBy('sc.id')
            ->orderBy('total', 'DESC')
            ->limit(5)
            ->get()
            ->map(function ($data) {
                $shopCategories = DB::table('shop_categories')->select('slug', 'name')->where('id', $data->id)->first();
                $shopCategories->total = $data->total;
                return $shopCategories;
            });
        return $shopCategories;
    }
}
