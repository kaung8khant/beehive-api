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
    public static function validateDevice($request)
    {
        return Validator::make($request->all(), [
            'device_type' => 'required|in:ios,android,chrome,firefox,edge,safari',
            'identifier' => 'required',
        ]);
    }

    public static function getDeviceType($deviceType)
    {
        switch ($deviceType) {
            case 'ios':
                return 0;
            case 'android':
                return 1;
            case 'chrome':
                return 5;
            case 'safari':
                return 7;
            case 'firefox':
                return 8;
            default:
                return 5;
        }
    }

    public static function registerDevice($request)
    {
        $fields = [
            'device_type' => self::getDeviceType($request->device_type),
            'identifier' => $request->identifier,
            'timezone' => '+23400',
            'test_type' => 1,
        ];

        return OneSignal::addDevice($fields);
    }

    public static function validateUsers($request)
    {
        $rules = [
            'type' => 'required_without:group_slug|in:customer,admin,vendor',
            'slugs' => 'required_with:type|array',
            'group_slug' => 'required_without:type|exists:customer_groups,slug',
            'message' => 'required|string',
            'url' => 'nullable|url',
        ];

        if ($request->type === 'customer') {
            $rules['slugs.*'] = 'required|exists:customers,slug';
        } else {
            $rules['slugs.*'] = 'required|exists:users,slug';
        }

        return Validator::make($request->all(), $rules);
    }

    public static function getPlayerIdsByType($type, $slugs)
    {
        if ($type === 'customer') {
            $customerIds = Customer::whereIn('slug', $slugs)->pluck('id');
            $playerIds = CustomerDevice::whereIn('customer_id', $customerIds)->pluck('player_id');
        } else {
            $userIds = User::whereIn('slug', $slugs)->pluck('id');
            $playerIds = UserDevice::whereIn('user_id', $userIds)->pluck('player_id');
        }

        return $playerIds;
    }

    public static function getPlayerIdsByGroup($groupSlug)
    {
        $customerIds = CustomerGroup::where('slug', $groupSlug)->first()->customers()->pluck('id');
        return CustomerDevice::whereIn('customer_id', $customerIds)->pluck('player_id');
    }

    public static function validatePlayerID($request)
    {
        $rules = [
            'type' => 'required_without:group_slug|in:customer,admin,vendor',
        ];

        return Validator::make($request->all(), $rules);
    }
}
