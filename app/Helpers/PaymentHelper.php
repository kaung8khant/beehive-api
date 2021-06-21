<?php

namespace App\Helpers;

use App\Helpers\StringHelper;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

trait PaymentHelper
{
    public static function createKbzPay($validatedData)
    {
        $timestamp = Carbon::now()->timestamp;
        $notifyUrl = config('payment.kbz_pay.notify_url');
        $nonceStr = StringHelper::generateRandomStringLength32();
        $appId = config('payment.kbz_pay.app_id');
        $merchCode = config('payment.kbz_pay.merch_code');
        $merchOrderId = '00000001AABBCCDD';
        $totalAmount = strval($validatedData['subTotal'] + $validatedData['tax'] - $validatedData['promocode_amount']);
        
        $stringA = 'appid=' . $appId;
        $stringA .= '&merch_code=' . $merchCode;
        $stringA .= '&merch_order_id=' . $merchOrderId;
        $stringA .= '&method=kbz.payment.precreate';
        $stringA .= '&nonce_str=' . $nonceStr;
        $stringA .= '&notify_url=' . $notifyUrl;
        $stringA .= '&timestamp=' . $timestamp;
        $stringA .= '&total_amount=' . $totalAmount;
        $stringA .= '&trade_type=APP';
        $stringA .= '&trans_currency=MMK';
        $stringA .= '&version=1.0';

        $stringToSign = $stringA . '&key=' . config('payment.kbz_pay.app_key');
        $sign = strtoupper(hash('sha256', $stringToSign));

        $data = [
            'timestamp' => $timestamp,
            'notify_url' => $notifyUrl,
            'method' => 'kbz.payment.precreate',
            'nonce_str' => $nonceStr,
            'sign_type' => 'SHA256',
            'sign' => $sign,
            'version' => '1.0',
            'biz_content' => [
                'appid' => $appId,
                'merch_code' => $merchCode,
                'merch_order_id' => $merchOrderId,
                'trade_type' => 'APP',
                'total_amount' => $totalAmount,
                'trans_currency' => 'MMK',
            ],
        ];

        try {
            $client = new Client();

            $response = $client->post(
                config('payment.kbz_pay.create_url'),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'json' => $data,
                ]
            );

            $response = json_decode($response->getBody(), true);
            return $response;
        } catch (RequestException $e) {
            throw $e;
        }

        return $data;
    }
}

// {
//     "Request": {
//       "timestamp": "1535166225",
//       "method": "kbz.payment.precreate",
//       "notify_url": "http://xxxxxx",
//       "nonce_str": "5K8264ILTKCH16CQ2502SI8ZNMTM67VS",
//       "sign_type": "SHA256",
//       "sign": "768E0C18F7FF0450B6A652000068980335E5DD1067FD276994116E6799EE9FCC",
//       "version": "1.0",
//       "biz_content": {
//         "merch_order_id": "0101234123456789012",
//         "merch_code": "09991234567",
//         "appid": "kp1234567890987654321aabbccddeef ",
//         "trade_type": "APP",
//         "title": "iPhoneX",
//         "total_amount": "5000000",
//         "trans_currency": "MMK",
//         "timeout_express": "100m",
//         "callback_info": "title%3diphonex"
//       }
//     }
//   }
