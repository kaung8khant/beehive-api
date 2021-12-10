<?php

namespace App\Helpers;

use App\Helpers\StringHelper;
use App\Jobs\SendPushNotification;
use App\Jobs\SendSms;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderDriver;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait ShopOrderHelper
{
    public static function validateOrder($request, $customerSlug = false)
    {
        $validator = validator()->make(request()->all(), self::getRules($customerSlug));

        if ($validator->fails()) {
            logger()->channel('slack')->error('Shop validation order in v3: ' . json_encode($request->all()));
            logger()->channel('slack')->error('and response: ' . $validator->errors()->first());
        }

        $validatedData = $validator->validated();

        if (auth('customers')->check()) {
            $validatedData['customer_id'] = auth('customers')->user()->id;
            $validatedData['order_date'] = Carbon::now();
        } else {
            $validatedData['customer_id'] = Customer::where('slug', $validatedData['customer_slug'])->first()->id;
        }

        return $validatedData;
    }

    public static function getRules($customerSlug = false)
    {
        return [
            'slug' => 'required|unique:shop_orders',
            'order_date' => 'nullable',
            'special_instruction' => 'nullable',
            'payment_mode' => 'required|in:COD,CBPay,KPay,MABPay,Credit',
            'delivery_mode' => 'required|in:pickup,delivery',
            'promo_code' => 'nullable|string|exists:App\Models\Promocode,code',
            'customer_info' => 'required',
            'customer_info.customer_name' => 'required|string',
            'customer_info.phone_number' => 'required|string',
            'address' => 'required',
            'address.house_number' => 'nullable|string',
            'address.floor' => 'nullable|integer|min:0|max:50',
            'address.street_name' => 'nullable|string',
            'address.latitude' => 'nullable|numeric',
            'address.longitude' => 'nullable|numeric',
            'delivery_fee' => 'nullable|numeric',
            'order_items' => 'required|array',
            'order_items.*.slug' => 'required|string|exists:App\Models\Product,slug',
            'order_items.*.quantity' => 'required|integer',
            'order_items.*.variant_slug' => 'required|exists:App\Models\ProductVariant,slug',
        ];

        if ($customerSlug) {
            $rules['customer_slug'] = 'required|string|exists:App\Models\Customer,slug';
        }

        return $rules;
    }

    public static function notifySystem($order, $orderItems, $phoneNumber, $messageService)
    {
        self::sendPushNotifications($order, $orderItems);
        self::sendSmsNotifications($order, $orderItems, $phoneNumber, $messageService);
    }

    public static function sendPushNotifications($order, $orderItems, $message = null)
    {
        $order = json_decode(json_encode($order), true);
        self::sendAdminPushNotifications($order, $message);
        self::sendVendorPushNotifications($order, $orderItems, $message);
        self::sendDriverPushNotifications($order, $message);
    }

    private static function sendAdminPushNotifications($order, $message = null)
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->pluck('slug');

        $request = new Request();
        $request['slugs'] = $admins;
        $request['message'] = $message ? $message : 'A shop order has been received.';
        $request['data'] = self::preparePushData($order);

        $appId = config('one-signal.admin_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendPushNotification::dispatch($uniqueKey, $fields, 'admin');
    }

    private static function sendVendorPushNotifications($order, $orderItems, $message = null)
    {
        $shopIds = array_map(function ($item) {
            return Product::where('slug', $item['slug'])->value('shop_id');
        }, $orderItems);

        $shopIds = array_values(array_unique($shopIds));
        $vendors = User::whereIn('shop_id', $shopIds)->pluck('slug');

        $request = new Request();
        $request['slugs'] = $vendors;
        $request['message'] = $message ? $message : 'An order has been received.';
        $request['data'] = self::preparePushData($order);

        $appId = config('one-signal.vendor_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendPushNotification::dispatch($uniqueKey, $fields, 'vendor');
    }

    private static function sendDriverPushNotifications($order, $message = null)
    {
        $orderID = ShopOrder::where('slug', $order['slug'])->pluck('id');
        $driverID = ShopOrderDriver::where('shop_order_id', $orderID)->whereHas('status', function ($q) {
            $q->where('status', 'accepted');
        })->first();

        if ($driverID) {
            $driver = User::where('id', $driverID->user_id)->pluck('slug');
            $request = new Request();
            $request['slugs'] = $driver;
            $request['message'] = $message ? $message : 'An order has been updated.';

            $request['data'] = self::preparePushData($order, "driver_order_update");
            $request['android_channel_id'] = config('one-signal.android_channel_id');
            $request['url'] = 'hive://beehivedriver/job?&slug=' . $order['slug'] . '&orderStatus=' . $order['order_status'];

            $appId = config('one-signal.admin_app_id');

            $fields = OneSignalHelper::prepareNotification($request, $appId);
            $uniqueKey = StringHelper::generateUniqueSlug();

            $response = OneSignalHelper::sendPush($fields, 'admin');
        }
    }

    private static function preparePushData($order)
    {
        unset($order['created_by']);
        unset($order['updated_by']);
        unset($order['shop_order_items']);

        return [
            'type' => 'shop_order',
            'body' => $order,
        ];
    }

    public static function sendSmsNotifications($order, $orderItems, $customerPhoneNumber, $messageService)
    {
        // self::sendAdminSms();
        self::sendVendorSms($order, $orderItems, $messageService);
        self::sendCustomerSms($order, $customerPhoneNumber, $messageService);
    }

    // private static function sendAdminSms()
    // {
    //     $admins = User::whereHas('roles', function ($query) {
    //         $query->where('name', 'Admin');
    //     })->pluck('phone_number');

    //     $message = 'A shop order has been received.';
    //     $smsData = SmsHelper::prepareSmsData($message);
    //     $uniqueKey = StringHelper::generateUniqueSlug();

    //     if (count($admins) > 0) {
    //         SendSms::dispatch($uniqueKey, $admins, $message, 'order', $smsData);
    //     }
    // }

    private static function sendVendorSms($order, $orderItems, $messageService)
    {
        $vendors = collect($orderItems)->map(function ($item) {
            $shopId = Product::where('slug', $item['slug'])->value('shop_id');
            return Shop::where('id', $shopId)->value('notify_numbers');
        })->filter()->collapse()->unique()->values();

        $message = Setting::where('key', 'vendor_shop_order_create')->value('value');
        $message = SmsHelper::parseShopSmsMessage($order, $message);

        // $message = 'An order has been received.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        if (count($vendors) > 0) {
            SendSms::dispatch($uniqueKey, $vendors, $message, 'order', $smsData, $messageService);
        }
    }

    private static function sendCustomerSms($order, $phoneNumber, $messageService)
    {
        $message = Setting::where('key', 'customer_shop_order_create')->value('value');
        $message = SmsHelper::parseShopSmsMessage($order, $message);

        // $message = 'Your order has successfully been created.';
        $smsData = SmsHelper::prepareSmsData($message);
        $uniqueKey = StringHelper::generateUniqueSlug();

        SendSms::dispatch($uniqueKey, [$phoneNumber], $message, 'order', $smsData, $messageService);
    }
}
