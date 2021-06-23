<?php

namespace App\Helpers;

use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use App\Models\ShopOrderDriver;
use App\Models\ShopOrderDriverStatus;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ladumor\OneSignal\OneSignal;

trait OrderAssignHelper{

    use OneSignalHelper;

    public static function assignOrder($type,$slug){
        $driverList = self::getdriver();
        $driverSlug =  key($driverList);

        if($type=="shop"){
            $order = ShopOrder::where('slug', $slug)->first();
        }else{
            $order = RestaurantOrder::where('slug', $slug)->first();
        }

        self::assignAndAlert($driverSlug,$order,$type);
    }



    public static function assignOrderToOther(){
        $driverList = self::getdriver();
        $shop_order_list = DB::select('Select od.id,od.shop_order_id,od.user_id,ods.status from (SELECT sod1.* FROM shop_order_drivers sod1
        JOIN (SELECT shop_order_id, MAX(created_at) created_at FROM shop_order_drivers GROUP BY shop_order_id) sod2
            ON sod1.shop_order_id = sod2.shop_order_id AND sod1.created_at = sod2.created_at) od left join shop_order_driver_statuses ods on od.id=ods.shop_order_driver_id where ods.status="pending"');
         
        foreach($shop_order_list as $order){
            $assignedDriver = User::where('id',$order->user_id)->first()->slug;
            $drivers = $driverList;
            
            ShopOrderDriverStatus::create([
                'shop_order_driver_id' => $order->id,
                'status' => "no_response",
            ]);


            unset($drivers[$assignedDriver]);
            $driverSlug =  key($drivers);

            self::assignAndAlert($driverSlug,$order,'shop');
        }

        $restaurant_orders = DB::select('Select * from (SELECT sod1.* FROM restaurant_order_drivers sod1
        JOIN (SELECT restaurant_order_id, MAX(created_at) created_at FROM restaurant_order_drivers GROUP BY restaurant_order_id) sod2
            ON sod1.restaurant_order_id = sod2.restaurant_order_id AND sod1.created_at = sod2.created_at) od left join restaurant_order_driver_statuses ods on od.id=ods.restaurant_order_driver_id where ods.status="pending"');
         
        foreach($restaurant_orders as $order){
            $assignedDriver = User::where('id',$order->user_id)->first()->slug;
            $drivers = $driverList;
        
            unset($drivers[$assignedDriver]);
            $driverSlug =  key($drivers);

            self::assignAndAlert($driverSlug,$order,'restaurant');
        }
    }
    
    private static function assignAndAlert($driverSlug,$order,$type){

        $driverID= User::where('slug',$driverSlug)->first()->id;
        if($type=="shop"){
            $shopOrderDriver = ShopOrderDriver::create([
                'shop_order_id' => $order->id,
                'user_id' => $driverID,
            ]);
            ShopOrderDriverStatus::create([
                'shop_order_driver_id' => $shopOrderDriver->id,
                'status' => "pending",
            ]);
        }else{
            $resOrderDriver = RestaurantOrderDriver::create([
                'restaurant_order_id' => $order->id,
                'user_id' => $driverID,
            ]);
            RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $resOrderDriver->id,
                'status' => "pending",
            ]);
        }
       
        
        $userIds = User::whereIn('slug', array($driverSlug))->pluck('id');
        $playerIds = UserDevice::whereIn('user_id', $userIds)->pluck('player_id');
        
        $fields['include_player_ids'] = $playerIds;
        $fields['data'] = ['slug'=>$order->slug,'price'=>$order->total_amount];
        $message = "test";
        OneSignal::sendPush($fields, $message);
    }

    private static function getdriver(){
        $database = app('firebase.database');
        $driverlist = $database->getReference('/driver')->getSnapshot()->getValue();
        
        $driverlist = self::getActiveDriver($driverlist);

        foreach($driverlist as $key=>$driver){
            $driverlist[$key]['distance'] = self::calculateDistance(16.811289, 96.1696837,$driver['location']['lat'],$driver['location']['lng']);
        }
        
         array_multisort(array_column($driverlist, 'distance'), SORT_ASC, $driverlist);
        
  
        return $driverlist;
    }

    private static function getActiveDriver($drivers){
        foreach($drivers as $key=>$driver){
            
            if(Carbon::parse($driver['updated_at'])<Carbon::now()->subMinutes(1)){
                unset($drivers[$key]);
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