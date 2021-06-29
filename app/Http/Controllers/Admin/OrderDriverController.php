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

        $restaurantOrder = RestaurantOrder::where('slug', $slug)->first();

        $restaurantOrderDriver = RestaurantOrderDriver::where('restaurant_order_id', $restaurantOrder->id)->where('user_id', $userId)->first();

        RestaurantOrderDriverStatus::create([
            'restaurant_order_driver_id' => $restaurantOrderDriver->id,
            'status' => "accepted",
        ]);


        return response()->json(['message' => 'created'], 201);
    }

    public function jobReject($slug)
    {

        $userId = Auth::guard('users')->user()->id;

        $shopOrder = ShopOrder::where('slug', $slug)->first();
        $restaurantOrder = RestaurantOrder::where('slug', $slug)->first();

        if (!empty($shopOrder) && isset($shopOrder)) {
            $shopOrderDriver = ShopOrderDriver::where('shop_order_id', $shopOrder->id)->where('user_id', $userId)->first();

            ShopOrderDriverStatus::create([
                'shop_order_driver_id' => $shopOrderDriver->id,
                'status' => "rejected",
            ]);
        } else {
            $restaurantOrder = RestaurantOrderDriver::where('restaurant_order_id', $shopOrder)->where('user_id', $userId)->first();
            RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $restaurantOrder->id,
                'status' => "rejected ",
            ]);
        }

        return response()->json(['message' => 'created'], 201);
    }

    public function jobDetail($slug)
    {

        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact')->where('slug', $slug)->first();


        return $this->generateResponse($restaurantOrder, 200);
    }

    public function jobList(Request $request)
    {
        $driver =  Auth::guard('users')->user()->id;

        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact')->where('order_status', '!=', 'cancelled')->orderByDesc('id')->whereHas('drivers.status', function ($q) use ($driver) {
            $q->where('status', '!=', 'rejected');
            $q->where('status', '!=', 'no_response');
        })->whereHas('drivers', function ($q) use ($driver) {
            $q->where('user_id', $driver);
        })->get();


        return response()->json($restaurantOrder, 200);
    }
}
