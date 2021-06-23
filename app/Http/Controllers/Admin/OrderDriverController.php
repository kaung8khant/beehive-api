<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\ShopOrder;
use App\Models\ShopOrderDriver;
use App\Models\ShopOrderDriverStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class OrderDriverController extends Controller
{
    use ResponseHelper;

    public function jobAccept($slug)
    {

        $userId = Auth::guard('users')->user()->id;

        $shopOrder = ShopOrder::where('slug', $slug)->first();
        $restaurantOrder = RestaurantOrder::where('slug', $slug)->first();

        if (!empty($shopOrder) && isset($shopOrder)) {
            $shopOrderDriver = ShopOrderDriver::where('shop_order_id',$shopOrder->id)->where('user_id',$userId)->first();
          
            ShopOrderDriverStatus::create([
                'shop_order_driver_id' => $shopOrderDriver->id,
                'status' => "accepted",
            ]);
        } else {
            $restaurantOrder = RestaurantOrderDriver::where('restaurant_order_id',$shopOrder)->where('user_id',$userId)->first();
            RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $restaurantOrder->id,
                'status' => "accepted",
            ]);
        }

        return response()->json(['message' => 'created'], 201);
    }

    public function jobReject($slug)
    {

        $userId = Auth::guard('users')->user()->id;

        $shopOrder = ShopOrder::where('slug', $slug)->first();
        $restaurantOrder = RestaurantOrder::where('slug', $slug)->first();

        if (!empty($shopOrder) && isset($shopOrder)) {
            $shopOrderDriver = ShopOrderDriver::where('shop_order_id',$shopOrder->id)->where('user_id',$userId)->first();
          
            ShopOrderDriverStatus::create([
                'shop_order_driver_id' => $shopOrderDriver->id,
                'status' => "rejected",
            ]);
        } else {
            $restaurantOrder = RestaurantOrderDriver::where('restaurant_order_id',$shopOrder)->where('user_id',$userId)->first();
            RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $restaurantOrder->id,
                'status' => "rejected ",
            ]);
        }

        return response()->json(['message' => 'created'], 201);
    }

    public function jobDetail($slug)
    {
        $shopOrder = ShopOrder::where('slug', $slug)
            ->with('contact')->firstOrFail();

        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status')->where('slug', $slug)->first();

        if (!empty($shopOrder) && isset($shopOrder)) {
            $shopOrder['type'] = "shop";
            return $this->generateShopOrderResponse($shopOrder, 200);
        } else {
            return $this->generateResponse($restaurantOrder, 200);
        }

    }

    public function jobList(Request $request)
    {
        $driver =  Auth::guard('users')->user()->id;
        $shopOrder = ShopOrder::with('drivers', 'drivers.status', 'contact')->whereHas('drivers.status', function ($q) use ($driver) {
            $q->where('status', 'accepted');
        })->whereHas('drivers', function ($q) use ($driver) {
            $q->where('user_id', $driver);
        })->get();
        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact')->whereHas('drivers.status', function ($q) use ($driver) {
            $q->where('status', 'accepted');
        })->whereHas('drivers', function ($q) use ($driver) {
            $q->where('user_id', $driver);
        })->get();

        $response = null;
        if (!empty($shopOrder) && isset($shopOrder)) {
            foreach ($shopOrder as $order) {
                $order['type'] = "shop";
            }
        }
        if (!empty($restaurantOrder) && isset($restaurantOrder)) {
            foreach ($restaurantOrder as $order) {
                $order['type'] = "restaurant";
            }
        }
        $response = $shopOrder->merge($restaurantOrder);
        return response()->json($response, 200);

    }
}
