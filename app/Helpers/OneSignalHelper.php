<?php

namespace App\Helpers;

use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\CustomerGroup;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Validator;
use Ladumor\OneSignal\OneSignal;

trait OneSignalHelper
{
    // public static function validateDevice($request)
    // {
    //     return Validator::make($request->all(), [
    //         'device_type' => 'required|in:ios,android,chrome,firefox,edge,safari',
    //         'identifier' => 'required',
    //     ]);
    // }

    // public static function getDeviceType($deviceType)
    // {
    //     switch ($deviceType) {
    //         case 'ios':
    //             return 0;
    //         case 'android':
    //             return 1;
    //         case 'chrome':
    //             return 5;
    //         case 'safari':
    //             return 7;
    //         case 'firefox':
    //             return 8;
    //         default:
    //             return 5;
    //     }
    // }

    // public static function registerDevice($request)
    // {
    //     $fields = [
    //         'device_type' => self::getDeviceType($request->device_type),
    //         'identifier' => $request->identifier,
    //         'timezone' => '+23400',
    //         'test_type' => 1,
    //     ];

    //     return OneSignal::addDevice($fields);
    // }

    // public static function validateUsers($request)
    // {
    //     $rules = [
    //         'type' => 'required_without:group_slug|in:customer,admin,vendor',
    //         'slugs' => 'required_with:type|array',
    //         'group_slug' => 'required_without:type|exists:customer_groups,slug',
    //         'message' => 'required|string',
    //         'url' => 'nullable|url',
    //     ];

    //     if ($request->type === 'customer') {
    //         $rules['slugs.*'] = 'required|exists:customers,slug';
    //     } else {
    //         $rules['slugs.*'] = 'required|exists:users,slug';
    //     }

    //     return Validator::make($request->all(), $rules);
    // }

    // public static function getPlayerIdsByType($type, $slugs)
    // {
    //     if ($type === 'customer') {
    //         $customerIds = Customer::whereIn('slug', $slugs)->pluck('id');
    //         $playerIds = CustomerDevice::whereIn('customer_id', $customerIds)->pluck('player_id');
    //     } else {
    //         $userIds = User::whereIn('slug', $slugs)->pluck('id');
    //         $playerIds = UserDevice::whereIn('user_id', $userIds)->pluck('player_id');
    //     }

    //     return $playerIds;
    // }

    // public static function getPlayerIdsByGroup($groupSlug)
    // {
    //     $customerIds = CustomerGroup::where('slug', $groupSlug)->first()->customers()->pluck('id');
    //     return CustomerDevice::whereIn('customer_id', $customerIds)->pluck('player_id');
    // }

    public static function validatePlayerID($request)
    {
        $rules = [
            'type' => 'required_without:group_slug|in:customer,admin,vendor',
        ];

        return Validator::make($request->all(), $rules);
    }

    public static function validateVendorsAndAdmins($request)
    {
        return Validator::make($request->all(), [
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:users,slug',
            'message' => 'required|string',
            'url' => 'nullable|url',
        ]);
    }

    public static function prepareNotification($request, $appId)
    {
        $fields = [
            'app_id' => $appId,
            'include_external_user_ids' => $request->slugs,
            'contents' => [
                'en' => $request->message,
            ],
        ];

        if ($request->url) {
            $fields['url'] = $request->url;
        }
        if ($request->url) {
            $fields['app_url'] = $request->app_url;
        }

        if ($request->data) {
            $fields['data'] = $request->data;
        }

        return json_encode($fields);
    }

    public static function sendPush($fields, $type)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
            curl_setopt($ch, CURLOPT_HTTPHEADER, self::getHeaders($type));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!empty($err)) { // return  error
                return json_decode($err, true);
            }

            return json_decode($response, true); // return success
        } catch (\Exception $exception) {
            return [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];
        }
    }

    private static function getHeaders($type)
    {
        return [
            'Content-Type: application/json; charset=utf-8',
            'X-Requested-With:XMLHttpRequest',
            'Authorization: Basic ' . self::getAuthorization($type),
        ];
    }

    private static function getAuthorization($type)
    {
        switch ($type) {
            case 'admin':
                return config('one-signal.admin_api_key');
            case 'vendor':
                return config('one-signal.vendor_api_key');
            case 'default':
                return null;
        }
    }
}
