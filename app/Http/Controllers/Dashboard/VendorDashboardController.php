<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Product;
use App\Models\ShopOrder;
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
            } elseif ($this->userRole === 'Shop') {
                $this->vendorId = $user->shop_id;
            }
        }
    }

    public function getOrderData()
    {
        if ($this->userRole === 'Restaurant') {
            $result = $this->getRestaurantOrderData();
        } elseif ($this->userRole === 'Shop') {
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
        } elseif ($this->userRole === 'Shop') {
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
        } elseif ($this->userRole === 'Shop') {
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

    public function getTopSellings()
    {
        if ($this->userRole === 'Restaurant') {
            $result = $this->getRestaurantTopSellings();
        } elseif ($this->userRole === 'Shop') {
            $result = $this->getShopTopSellings();
        }

        return response()->json($result);
    }

    private function getRestaurantTopSellings()
    {
        $menusCount = DB::table('restaurant_order_items as oi')
            ->join('restaurant_orders as o', 'o.id', '=', 'oi.restaurant_order_id')
            ->where('o.restaurant_branch_id', $this->vendorId)
            ->select('oi.menu_id', DB::raw('count(*) as total_orders'))
            ->groupBy('menu_id')
            ->orderBy('total_orders', 'DESC')
            ->limit(10)
            ->get();

        $result = [];
        foreach ($menusCount as $key) {
            if ($key->menu_id) {
                $menu = Menu::find($key->menu_id);

                $topSellingMenu = [
                    'slug' => $menu->slug,
                    'item_name' => $menu->name,
                    'total_orders' => $key->total_orders,
                    'total_earning' => $this->getTotalEarningByMenu($menu->id),
                    'images' => $menu->images,
                ];

                array_push($result, $topSellingMenu);
            }
        }

        return $result;
    }

    private function getTotalEarningByMenu($menuId)
    {
        return DB::table('restaurant_order_items')
            ->where('menu_id', $menuId)
            ->sum(DB::raw('(amount + tax - discount) * quantity'));
    }

    private function getShopTopSellings()
    {
        $productsCount = DB::table('shop_order_items')
            ->where('shop_id', $this->vendorId)
            ->select('product_id', DB::raw('count(*) as total_orders'))
            ->groupBy('product_id')
            ->orderBy('total_orders', 'DESC')
            ->limit(10)
            ->get();

        $result = [];
        foreach ($productsCount as $key) {
            if ($key->product_id) {
                $product = Product::find($key->product_id);

                $topSellingProduct = [
                    'slug' => $product->slug,
                    'item_name' => $product->name,
                    'total_orders' => $key->total_orders,
                    'total_earning' => $this->getTotalEarningByProduct($product->id),
                    'images' => $product->images,
                ];

                array_push($result, $topSellingProduct);
            }
        }

        return $result;
    }

    private function getTotalEarningByProduct($productId)
    {
        return DB::table('shop_order_items')
            ->where('product_id', $productId)
            ->sum(DB::raw('(amount + tax - discount) * quantity'));
    }

    public function getRecentOrders()
    {
        if ($this->userRole === 'Restaurant') {
            $result = $this->getRestaurantRecentOrders();
        } elseif ($this->userRole === 'Shop') {
            $result = $this->getShopRecentOrders();
        }

        return response()->json($result);
    }

    private function getRestaurantRecentOrders()
    {
        $orders = DB::table('restaurant_orders')
            ->where('restaurant_branch_id', $this->vendorId)
            ->select('id', 'order_status', 'slug')
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get();

        $result = [];
        foreach ($orders as $key) {
            $order = [
                'order_id' => $key->slug,
                'location' => $this->getRestaurantContactLocation($key->id),
                'status' => $key->order_status,
                'delivered_time' => $this->getRestaurantDeliveredTime($key->id),
            ];

            array_push($result, $order);
        }

        return $result;
    }

    private function getRestaurantContactLocation($orderId)
    {
        return DB::table('townships as t')
            ->join('restaurant_order_contacts as oc', 'oc.township_id', '=', 't.id')
            ->where('oc.restaurant_order_id', $orderId)
            ->value('t.name');
    }

    private function getRestaurantDeliveredTime($orderId)
    {
        return DB::table('restaurant_order_statuses')
            ->where('restaurant_order_id', $orderId)
            ->where('status', 'delivered')
            ->value('created_at');
    }

    private function getShopRecentOrders()
    {
        $orders = DB::table('shop_order_vendors')
            ->where('shop_id', $this->vendorId)
            ->select('id', 'shop_order_id', 'order_status')
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get();


        $result = [];
        foreach ($orders as $key) {
            $orderSlug=ShopOrder::where('id', $key->id)->firstOrFail()->slug;
            $order = [
                'order_id' => $orderSlug,
                'location' => $this->getShopContactLocation($key->shop_order_id),
                'status' => $key->order_status,
                'delivered_time' => $this->getShopDeliveredTime($key->id),
            ];

            array_push($result, $order);
        }

        return $result;
    }

    private function getShopContactLocation($orderId)
    {
        return DB::table('townships as t')
            ->join('shop_order_contacts as oc', 'oc.township_id', '=', 't.id')
            ->where('oc.shop_order_id', $orderId)
            ->value('t.name');
    }

    private function getShopDeliveredTime($orderVendorId)
    {
        return DB::table('shop_order_statuses')
            ->where('shop_order_vendor_id', $orderVendorId)
            ->where('status', 'delivered')
            ->value('created_at');
    }
}
