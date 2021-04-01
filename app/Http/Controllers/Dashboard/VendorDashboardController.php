<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
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

    private function getShopOrderStatus()
    {
        return null;
    }

    private function getShopRating()
    {
        $rating = DB::table('shop_ratings')
            ->where('target_id', $this->vendorId)
            ->where('target_type', 'shop')
            ->avg('rating');

        return $rating ? round($rating, 1) : null;
    }
}
