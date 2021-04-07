<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorDashboardController extends Controller
{
    protected $userRole;
    protected $vendorId;

    public function __construct()
    {
        if (Auth::guard('vendors')->check()) {
            $user = Auth::guard('vendors')->user();
            $this->userRole = $user->roles[0]->name;

            if ($this->userRole === 'Restaurant') {
                $this->vendorId = $user->restaurant_branch_id;
            } else if ($this->userRole === 'Shop') {
                $this->vendorId = $user->shop_id;
            }
        }
    }

    public function getOrderData()
    {
        if ($this->userRole === 'Restaurant') {
            $result = $this->getRestaurantOrderData();
        } else if ($this->userRole === 'Shop') {
            $result = $this->getShopOrderData();
        }

        return response()->json($result);
    }

    private function getRestaurantOrderData()
    {
        return [
            'pending_orders' => $this->getRestaurantOrderStatus('pending'),
            'preparing_orders' => $this->getRestaurantOrderStatus('preparing'),
            'delivered_orders' => $this->getRestaurantOrderStatus('delivered'),
            'cancelled_orders' => $this->getRestaurantOrderStatus('cancelled'),
            'total_orders' => $this->getRestaurantOrderStatus(),
            'rating' => $this->getRestaurantRating(),
        ];
    }

    private function getRestaurantOrderStatus($status = null)
    {
        $query = DB::table('restaurant_orders')
            ->where('restaurant_branch_id', $this->vendorId);

        if ($status) {
            $query->where('order_status', $status);
        }

        return $query->count();
    }

    private function getRestaurantRating()
    {
        $restaurantId = DB::table('restaurant_branches')->find($this->vendorId)->restaurant_id;

        $rating = DB::table('restaurant_ratings')
            ->where('target_id', $restaurantId)
            ->where('target_type', 'restaurant')
            ->avg('rating');

        return $rating ? round($rating, 1) : null;
    }

    private function getShopOrderData()
    {
        return [
            'pending_orders' => $this->getShopOrderStatus('pending'),
            'preparing_orders' => $this->getShopOrderStatus('preparing'),
            'delivered_orders' => $this->getShopOrderStatus('delivered'),
            'cancelled_orders' => $this->getShopOrderStatus('cancelled'),
            'total_orders' => $this->getShopOrderStatus(),
            'rating' => $this->getShopRating(),
        ];
    }

    private function getShopOrderStatus($status = null)
    {
        $query = DB::table('shop_order_vendors')
            ->where('shop_id', $this->vendorId);

        if ($status) {
            $query->where('order_status', $status);
        }

        return $query->count();
    }

    private function getShopRating()
    {
        $rating = DB::table('shop_ratings')
            ->where('target_id', $this->vendorId)
            ->where('target_type', 'shop')
            ->avg('rating');

        return $rating ? round($rating, 1) : null;
    }

    public function getDaywiseOrders()
    {
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();

        if ($this->userRole === 'Restaurant') {
            $result = $this->getRestaurantDaywiseOrders($startDate, $endDate);
        } else if ($this->userRole === 'Shop') {
            $result = $this->getShopDaywiseOrders($startDate, $endDate);
        }

        return response()->json($result);
    }

    private function getRestaurantDaywiseOrders($startDate, $endDate)
    {
        return DB::table('restaurant_orders')
            ->where('order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('restaurant_branch_id', $this->vendorId)
            ->select(DB::raw('DATE(order_date) AS date'), DB::raw('count(*) AS total_orders'))
            ->groupBy('date')
            ->get();
    }

    private function getShopDaywiseOrders($startDate, $endDate)
    {
        return DB::table('shop_order_vendors as ov')
            ->join('shop_orders as o', 'o.id', '=', 'ov.shop_order_id')
            ->where('o.order_date', '>', $startDate->format('Y-m-d H:i:s'))
            ->where('o.order_date', '<', $endDate->format('Y-m-d') . ' 23:59:59')
            ->where('ov.shop_id', $this->vendorId)
            ->select(DB::raw('DATE(o.order_date) AS date'), DB::raw('count(*) AS total_orders'))
            ->groupBy('date')
            ->get();
    }

    public function getTotalEarnings()
    {
        $today = Carbon::now();
        $yesterday = Carbon::now()->subDay();

        if ($this->userRole === 'Restaurant') {
            $result = [
                'today_earning' => $this->getRestaurantEarning($today),
                'yesterday_earning' => $this->getRestaurantEarning($yesterday),
            ];
        } else if ($this->userRole === 'Shop') {
            $result = [
                'today_earning' => $this->getShopEarning($today),
                'yesterday_earning' => $this->getShopEarning($yesterday),
            ];
        }

        return response()->json($result);
    }

    private function getRestaurantEarning($date)
    {
        return DB::table('restaurant_order_items as oi')
            ->join('restaurant_orders as o', 'o.id', '=', 'oi.restaurant_order_id')
            ->whereDate('o.order_date', $date->format('Y-m-d'))
            ->where('o.restaurant_branch_id', $this->vendorId)
            ->sum(DB::raw('(oi.amount + oi.tax - oi.discount) * oi.quantity'));
    }

    private function getShopEarning($date)
    {
        return DB::table('shop_order_items as oi')
            ->join('shop_order_vendors as ov', 'ov.id', '=', 'oi.shop_order_vendor_id')
            ->join('shop_orders as o', 'o.id', '=', 'ov.shop_order_id')
            ->whereDate('o.order_date', $date->format('Y-m-d'))
            ->where('oi.shop_id', $this->vendorId)
            ->sum(DB::raw('(oi.amount + oi.tax - oi.discount) * oi.quantity'));
    }
}
