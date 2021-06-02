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

    public function jobAccept(Request $request)
    {

        $userId = User::where('slug', $request['user_slug'])->firstOrFail()->id;

        $shopOrder = ShopOrder::where('slug', $request['order_slug'])->first();
        $restaurantOrder = ShopOrder::where('slug', $request['order_slug'])->first();

        if (!empty($shopOrder) && isset($shopOrder)) {
            $shopOrderDriver = ShopOrderDriver::create([
                'shop_order_id' => $shopOrder->id,
                'user_id' => $userId,
            ]);
            ShopOrderDriverStatus::create([
                'shop_order_driver_id' => $shopOrderDriver->id,
                'status' => "accepted",
            ]);
        } else {
            $restaurantOrder = RestaurantOrderDriver::create([
                'restaurant_order_id' => $restaurantOrder->id,
                'user_id' => $userId,
            ]);
            RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $restaurantOrder->id,
                'status' => "accepted",
            ]);
        }

        return response()->json(['message' => 'created'], 201);
    }

    public function jobDetail($slug)
    {
        $shopOrder = ShopOrder::where('slug', $slug)
            ->with('contact', 'contact.township')->firstOrFail();

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
        $driver = Auth::user()->id;
        $shopOrder = ShopOrder::with('drivers', 'drivers.status', 'contact',
            'contact.township')->whereHas('drivers.status', function ($q) use ($driver) {
            $q->where('status', 'accepted');
        })->whereHas('drivers', function ($q) use ($driver) {
            $q->where('user_id', $driver);
        })->get();
        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact', 'restaurantOrderContact.township')->whereHas('drivers.status', function ($q) use ($driver) {
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
