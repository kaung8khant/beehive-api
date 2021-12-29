<?php

namespace App\Services\OneSignalService;

use Illuminate\Support\Facades\Request;

class OneSignalRequest extends Request
{
    public $type;
    public $slugs;
    public $message;
    public $data;
    public $android_channel_id;
    public $url;
    public $appId;
    public $base_url;

    public function __construct($type, $slugs, $message)
    {

        $this->type = $type;
        $this->slugs = $slugs;
        $this->message = $message;

        switch ($type) {
            case 'admin':
                $this->appId = config('one-signal.admin_app_id');
                break;
            case 'vendor':
                $this->appId = config('one-signal.vendor_app_id');
                $this->android_channel_id = config('one-signal.vendor_channel_id');
                $this->base_url = config('one-signal.vendor_deeplink_url') . "/";
                break;
            case 'user':
                $this->appId = config('one-signal.user_app_id');
                $this->android_channel_id = config('one-signal.user_channel_id');
                $this->base_url = config('one-signal.user_deeplink_url') . "/";
                break;
            case 'driver':
                $this->appId = config('one-signal.admin_app_id');
                $this->android_channel_id = config('one-signal.android_channel_id');
                $this->base_url = config('one-signal.driver_deeplink_url') . "/";
                break;
            default:
                $this->appId = config('one-signal.admin_app_id');
                break;
        }
    }
}
