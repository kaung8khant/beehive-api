<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;

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
}
