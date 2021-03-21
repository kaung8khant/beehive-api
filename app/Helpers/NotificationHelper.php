<?php

namespace App\Helpers;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

trait NotificationHelper
{
    protected function notifyRestaurant($slug)
    {
        $shopId = RestaurantBranch::where("slug",$slug)->first()->id;
        $userId = User::where("restaurant_branch_id",$shopId)->first()->id;
        
        //Delete over 3 days token
        UserSession::where("updated_at",'<=',Carbon::now()->subDays(3))->forceDelete();

        $tokenList = UserSession::where('user_id',$userId)->pluck("device_token")->all();
        $this->sendNotification($data,$tokenList);
    }
    protected function notifyShop($slug,$data){
        $shopId = Shop::where("slug",$slug)->first()->id;
        $userId = User::where("shop_id",$shopId)->first()->id;
        
        //Delete over 3 days token
        UserSession::where("updated_at",'<=',Carbon::now()->subDays(3))->forceDelete();

        $tokenList = UserSession::where('user_id',$userId)->pluck("device_token")->all();
        $this->sendNotification($data,$tokenList);
    }

    private function sendNotification($data,$to){
          
        $data = [
            "registration_ids" => $to, //array
            "notification" => $data //array
        ];
        $dataString = json_encode($data);
    
        $headers = [
            'Authorization: key=' . $config('broadcasting.server_key'),
            'Content-Type: application/json',
        ];
    
        $ch = curl_init();
      
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
               
        $response = curl_exec($ch);
  
    }
}
