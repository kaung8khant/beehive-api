<?php

namespace App\Http\Controllers;

use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\ShopOrder;
use App\Models\ShopOrderDriver;
use App\Models\ShopOrderDriverStatus;
use App\Models\User;
use Illuminate\Http\Request;

class OrderDriverController extends Controller
{
    public function jobAccept(Request $request, $status = "accepted")
    {

        $userId = User::where('slug', $request['user_slug'])->firstOrFail()->id;

        $shopOrder = ShopOrder::where('slug', $request['order_slug'])->first();
        $restaurantOrder = ShopOrder::where('slug', $request['order_slug'])->first();

        if (!empty($shopOrder) && isset($shopOrder)) {
            $shopOrderDriver = ShopOrderDriver::create([
                'shop_order_id' => $shopOrder->id,
                'user_id' => $userId,
            ]);
            $shopOrderDriverStatus = ShopOrderDriverStatus::create([
                'shop_order_driver_id' => $shopOrderDriver->id,
                'status' => $status,
            ]);
        } else {
            $shopOrderDriver = RestaurantOrderDriver::create([
                'restaurant_order_id' => $restaurantOrder->id,
                'user_id' => $userId,
            ]);
            $shopOrderDriverStatus = RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $restaurantOrder->id,
                'status' => $status,
            ]);
        }

        return response()->json('created', 201);
    }

    public function jobDetail(Request $request, $slug)
    {
        $shopOrder = ShopOrder::with('drivers', 'drivers.status')->where('slug', $slug)->first();
        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status')->where('slug', $slug)->first();
        $response = null;

        if (!empty($shopOrder) && isset($shopOrder)) {
            $response = $shopOrder;
        } else {
            $response = $restaurantOrder;
        }

        return response()->json($response, 200);

    }
}
