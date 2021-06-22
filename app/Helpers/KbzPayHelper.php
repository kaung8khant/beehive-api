<?php

namespace App\Helpers;

use App\Helpers\StringHelper;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

trait KbzPayHelper
{
    public static function createKbzPay($validatedData, $orderType)
    {
        $paymentRequest = self::buildPaymentRequest($validatedData, $orderType);

        try {
            $client = new Client();

            $response = $client->post(
                config('payment.kbz_pay.create_url'),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'json' => ['Request' => $paymentRequest],
                ]
            );

            $response = json_decode($response->getBody(), true);
            return $response;
        } catch (RequestException $e) {
            return false;
        }
    }

    private static function buildPaymentRequest($validatedData, $orderType)
    {
        $paymentRequest = [
            'timestamp' => Carbon::now()->timestamp,
            'notify_url' => config('payment.kbz_pay.notify_url'),
            'method' => 'kbz.payment.precreate',
            'nonce_str' => StringHelper::generateRandomStringLength32(),
            'version' => '1.0',
        ];

        $bizData = [
            'appid' => config('payment.kbz_pay.app_id'),
            'merch_code' => config('payment.kbz_pay.merch_code'),
            'merch_order_id' => strtoupper($orderType) . '-' . $validatedData['slug'],
            'trade_type' => 'APP',
            'total_amount' => self::getTotalAmount($validatedData),
            'trans_currency' => 'MMK',
            'timeout_express' => '15m',
        ];

        $signKeyVal = array_merge($paymentRequest, $bizData);
        $sign = self::signature(self::joinKeyValue($signKeyVal));

        $paymentRequest['sign_type'] = 'SHA256';
        $paymentRequest['sign'] = $sign;
        $paymentRequest['biz_content'] = $bizData;

        return $paymentRequest;
    }

    private static function getTotalAmount($validatedData)
    {
        $totalAmount = $validatedData['subTotal'] + $validatedData['tax'];

        if (isset($validatedData['promocode_amount'])) {
            $totalAmount = $totalAmount - $validatedData['promocode_amount'];
        }

        return strval($totalAmount);
    }

    public static function joinKeyValue(array $arr)
    {
        ksort($arr);

        $joinKeyVal = function (&$val, $key) {
            $val = "$key=$val";
        };

        array_walk($arr, $joinKeyVal);

        return implode('&', $arr);
    }

    public static function signature($text)
    {
        $stringToSign = $text . '&key=' . config('payment.kbz_pay.app_key');
        return strtoupper(hash('sha256', $stringToSign));
    }
}
