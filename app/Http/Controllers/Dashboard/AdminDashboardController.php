<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function getCountData()
    {
        $result = [
            'restaurants' => DB::table('restaurants')->count(),
            'shops' => DB::table('shops')->count(),
            'customers' => DB::table('customers')->count(),
            'drivers' => $this->getDriversCount(),
            'promo_codes' => DB::table('promocodes')->count(),
        ];

        return response()->json($result);
    }

    public function getRestaurantOrders()
    {
        $paginator = DB::table('restaurant_orders')
            ->select('restaurant_branch_id', DB::raw('count(*) AS total_orders'))
            ->groupBy('restaurant_branch_id')
            ->orderby('total_orders', 'DESC')
            ->paginate(10);

        $data = [];

        foreach ($paginator->items() as $key) {
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
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $data,
        ];

        return response()->json($result);
    }

    public function getShopOrders()
    {
        $paginator = DB::table('shop_order_items')
            ->select('shop_id', DB::raw('count(*) AS total_orders'))
            ->groupBy('shop_id')
            ->orderBy('total_orders', 'DESC')
            ->paginate(10);

        $data = [];

        foreach ($paginator as $key) {
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
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $data,
        ];

        return response()->json($result);
    }

    public function getOrderChartData(Request $request)
    {
        if ($request->type === 'weekly') {
            $result = $this->getWeeklyOrders();
        } else if ($request->type === 'monthly') {
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
            ->select(DB::raw('MONTH(order_date) AS month_number'), DB::raw('DATE_FORMAT(order_date, "%b") AS month'), DB::raw('count(*) AS total_orders'))
            ->groupBy('month_number', 'month')
            ->orderBy('month_number')
            ->get();

        $shopOrders = DB::table('shop_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->select(DB::raw('MONTH(order_date) AS month_number'), DB::raw('DATE_FORMAT(order_date, "%b") AS month'), DB::raw('count(*) AS total_orders'))
            ->groupBy('month_number', 'month')
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
            ->select(DB::raw('DATE(order_date) AS date'), DB::raw('count(*) AS total_orders'))
            ->groupBy('date')
            ->get();

        $shopOrders = DB::table('shop_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->select(DB::raw('DATE(order_date) AS date'), DB::raw('count(*) AS total_orders'))
            ->groupBy('date')
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
            ->select(DB::raw('DATE(order_date) AS date'), DB::raw('count(*) AS total_orders'))
            ->groupBy('date')
            ->get();

        $shopOrders = DB::table('shop_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->select(DB::raw('DATE(order_date) AS date'), DB::raw('count(*) AS total_orders'))
            ->groupBy('date')
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
}
