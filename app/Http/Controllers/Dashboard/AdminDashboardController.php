<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\Restaurant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function getCountData()
    {
        $result = [
            'restaurants' => Restaurant::count(),
            'shops' => Shop::count(),
            'customers' => Customer::count(),
            'drivers' => $this->getDriversCount(),
            'promo_codes' => Promocode::count(),
        ];

        return response()->json($result);
    }

    public function getRestaurantOrders()
    {
        $paginator = DB::table('restaurant_orders')
            ->select('restaurant_branch_id', DB::raw('count(*) as total_orders'))
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
                    ->sum(DB::raw('amount + tax - discount'));

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
            ->select('shop_id', DB::raw('count(*) as total_orders'))
            ->groupBy('shop_id')
            ->orderBy('total_orders', 'DESC')
            ->paginate(10);

        $data = [];

        foreach ($paginator as $key) {
            $shop = DB::table('shops')->where('id', $key->shop_id)->first();

            if ($shop) {
                $totalAmount = DB::table('shop_order_items')
                    ->where('shop_id', $key->shop_id)
                    ->sum(DB::raw('amount + tax - discount'));

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

    public function getOrderChartData()
    {

    }

    private function getDriversCount()
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'Driver');
        })->count();
    }
}
