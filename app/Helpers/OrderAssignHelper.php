<?php

namespace App\Helpers;

use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\ShopOrder;
use App\Models\ShopOrderDriver;
use App\Models\ShopOrderDriverStatus;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Ladumor\OneSignal\OneSignal;
use App\Helpers\RestaurantOrderHelper as OrderHelper;
use Illuminate\Http\Request;

trait OrderAssignHelper
{

    use OneSignalHelper;

    public static function assignOrder($type, $slug)
    {
        $driverList = self::getdriver();
        $driverSlug = "";
        $order = null;
        if (isset($driverList) && count($driverList) > 0) {
            $driverSlug =  key($driverList);

            if ($type == "shop") {
                $order = ShopOrder::where('slug', $slug)->first();
            } else {
                $order = RestaurantOrder::where('slug', $slug)->first();
            }
        }

        self::assignAndAlert($driverSlug, $order);
    }



    public static function assignOrderToOther()
    {
        $driverList = self::getdriver();

        $restaurant_orders = DB::select('Select od.id,od.restaurant_order_id,od.user_id,ods.status from (SELECT sod1.* FROM restaurant_order_drivers sod1
        JOIN (SELECT restaurant_order_id, MAX(created_at) created_at FROM restaurant_order_drivers GROUP BY restaurant_order_id) sod2
            ON sod1.restaurant_order_id = sod2.restaurant_order_id AND sod1.created_at = sod2.created_at) od left join restaurant_order_driver_statuses ods on od.id=ods.restaurant_order_driver_id where ods.status="pending"');

        foreach ($restaurant_orders as $order) {
            $reOrder = RestaurantOrderDriverStatus::where('restaurant_order_driver_id', $order->id)->get();

            $assignedDriver = User::where('id', $order->user_id)->first()->slug;
            $drivers = $driverList;

            unset($drivers[$assignedDriver]);

            $driverSlug =  key($drivers);


            $resOrderDriver = RestaurantOrderDriver::where('user_id', $order->user_id)->where('restaurant_order_id', $order->restaurant_order_id)->first();
            RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $resOrderDriver->id,
                'status' => "no-response",
            ]);
            $restaurantOrder = RestaurantOrder::where('id', $order->restaurant_order_id)->first();
            if (count($reOrder) < 2) {
                self::assignAndAlert($driverSlug, $restaurantOrder);
            } else {
                OrderHelper::createOrderStatus($restaurantOrder->id, "cancelled");

                $request = new Request();
                $request['slugs'] = User::with('roles')->whereHas('roles', function ($query) {
                    return $query->where('name', "Admin");
                })->get()->pluck('slug');

                $request['message'] = "No driver has been assigned to order " . $restaurantOrder->slug . " and has been cancelled";

                $appId = config('one-signal.admin_app_id');

                $fields = OneSignalHelper::prepareNotification($request, $appId);
                $response = OneSignalHelper::sendPush($fields, 'admin');
            }
        }
    }

    private static function assignAndAlert($driverSlug, $order)
    {
        if (isset($order) && !empty($driverSlug)) {
            return false;
        }
        $driver = User::where('slug', $driverSlug)->first();

        if (isset($driver)) {
            $driverID = $driver->id;
            $resOrderDriver = RestaurantOrderDriver::where('restaurant_order_id', $order->id)->where('user_id', $driverID)->first();
            if (empty($resOrderDriver)) {
                $resOrderDriver = RestaurantOrderDriver::create([
                    'restaurant_order_id' => $order->id,
                    'user_id' => $driverID,
                ]);
                RestaurantOrderDriverStatus::create([
                    'restaurant_order_driver_id' => $resOrderDriver->id,
                    'status' => "pending",
                ]);
            }


            $request = new Request();
            $request['slugs'] = array($driverSlug);
            $request['message'] = "You have received new order. Accept Now!";
            $appId = config('one-signal.admin_app_id');
            $order = RestaurantOrder::where('id', $order->id)->first();
            $request['data'] = ["slug" => $order->slug, 'price' => $order->total_amount];
            $fields = OneSignalHelper::prepareNotification($request, $appId);


            $response = OneSignalHelper::sendPush($fields, 'admin');
        }
    }

    private static function getdriver()
    {
        $database = app('firebase.database');
        $driverlist = $database->getReference('/driver')->getSnapshot()->getValue();

        $driverlist = self::getActiveDriver($driverlist);

        if (isset($dirvers) && count($drivers) > 0) {
            foreach ($driverlist as $key => $driver) {
                $driverlist[$key]['distance'] = self::calculateDistance(16.811289, 96.1696837, $driver['location']['lat'], $driver['location']['lng']);
            }
            array_multisort(array_column($driverlist, 'distance'), SORT_ASC, $driverlist);
        }




        return $driverlist;
    }

    private static function getActiveDriver($drivers)
    {
        if (isset($dirvers) && count($drivers) > 0) {
            foreach ($drivers as $key => $driver) {

                if (Carbon::parse($driver['updated_at']) < Carbon::now()->subMinutes(1)) {
                    unset($drivers[$key]);
                }
            }
        }

        return $drivers;
    }

    private static function calculateDistance($latFrom, $lngFrom, $latTo, $lngTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latFrom);
        $lonFrom = deg2rad($lngFrom);
        $latTo = deg2rad($latTo);
        $lonTo = deg2rad($lngTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        //distance in meter
        return round($angle * $earthRadius);
    }
}
