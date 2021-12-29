<?php

namespace App\Services\OneSignalService;

use Illuminate\Support\Facades\Log;

class OneSignalService implements OneSignalServiceInterface
{
    public  function sendPush($request)
    {
        $fields = $this->prepareNotification($request);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders($request->type));
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

    public  function prepareNotification($request)
    {
        $fields = [
            'app_id' => $request->appId,
            'include_external_user_ids' => $request->slugs,
            'contents' => [
                'en' => $request->message,
            ],
            'headings' => [
                'en' => $request->title ?? '',
            ],

        ];

        if ($request->url) {
            $fields['url'] = $request->base_url . $request->url;
        }

        if (isset($request->send_after)) {
            $fields['send_after'] = $request->send_after;
        }

        if ($request->android_channel_id) {
            $fields['android_channel_id'] = $request->android_channel_id;
        }

        if ($request->data) {
            $fields['data'] = $request->data;
        }
        return json_encode($fields);
    }


    private  function getHeaders($type)
    {
        return [
            'Content-Type: application/json; charset=utf-8',
            'X-Requested-With:XMLHttpRequest',
            'Authorization: Basic ' . $this->getAuthorization($type),
        ];
    }

    private  function getAuthorization($type)
    {
        switch ($type) {
            case 'admin':
                return config('one-signal.admin_api_key');
            case 'driver':
                return config('one-signal.admin_api_key');
            case 'vendor':
                return config('one-signal.vendor_api_key');
            case 'user':
                return config('one-signal.user_api_key');
            case 'default':
                return config('one-signal.admin_api_key');
        }
    }
}
