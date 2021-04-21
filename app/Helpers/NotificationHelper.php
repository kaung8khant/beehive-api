<?php

namespace App\Helpers;

use App\Models\RestaurantBranch;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Carbon;

trait NotificationHelper
{
    protected function notifyRestaurant($slug, $data)
    {
        $branch = RestaurantBranch::where("slug", $slug)->first();

        if ($branch) {
            $branchId = $branch->id;

            $user = User::where("restaurant_branch_id", $branchId)->first();
            if ($user) {
                $userId = $user->id;
                //Delete over 3 days token
                UserSession::where("updated_at", '<=', Carbon::now()->subDays(3))->forceDelete();

                $tokenList = UserSession::where('user_id', $userId)->pluck("device_token")->all();
                $this->sendNotification($data, $tokenList);
            }
        }
    }

    protected function notifyAdmin($data)
    {
        $users = User::with('roles')->whereHas('roles', function ($query) {
            return $query->where('name', "Admin");
        })->get();
        if ($users) {
            $tokenList = [];
            foreach ($users as $user) {
                $userId = $user->id;

                //Delete over 3 days token
                UserSession::where("updated_at", '<=', Carbon::now()->subDays(3))->forceDelete();

                $token = UserSession::where('user_id', $userId)->pluck("device_token")->all();
                $tokenList = array_merge($tokenList, $token);

            }
            $this->sendNotification($data, $tokenList);
        }
    }

    protected function notifyShop($slug, $data)
    {
        $shop = Shop::where("slug", $slug)->first();

        if ($shop) {

            $shopId = $shop->id;
            $user = User::where("shop_id", $shopId)->first();
            if ($user) {

                $userId = $user->id;

                //Delete over 3 days token
                UserSession::where("updated_at", '<=', Carbon::now()->subDays(3))->forceDelete();

                $tokenList = UserSession::where('user_id', $userId)->pluck("device_token")->all();

                $this->sendNotification($data, $tokenList);
            }
        }
    }

    private function sendNotification($data, $to)
    {
        $data = [
            "registration_ids" => $to, //array
            "notification" => $data, //array
        ];

        if (!empty($to)) {
            $dataString = json_encode($data);

            $headers = [
                'Authorization: key=' . config('broadcasting.firebase'),
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $result = curl_exec($ch);

        }

    }
}
