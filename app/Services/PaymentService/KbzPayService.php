<?php

namespace App\Services\PaymentService;

use App\Exceptions\ServerException;
use App\Helpers\KbzPayHelper;
use App\Helpers\StringHelper;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class KbzPayService extends PaymentService
{
    public function createTransaction($validatedData, $orderType)
    {
        $paymentRequest = $this->buildPaymentRequest($validatedData, $orderType);

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

            if (!$response || $response['Response']['code'] != '0' || $response['Response']['result'] != 'SUCCESS') {
                throw new ServerException('Error connecting to KBZ Pay service.');
            }

            return $response;
        } catch (RequestException $e) {
            throw new ServerException('Error connecting to KBZ Pay service.');
        }
    }

    private function buildPaymentRequest($validatedData, $orderType)
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
            'merch_order_id' => strtoupper($orderType) . '-' . $validatedData['slug'] . '-' . random_int(1000, 9999),
            'trade_type' => 'APP',
            'total_amount' => isset($validatedData['totalAmount']) ? strval($validatedData['totalAmount']) : $this->getTotalAmount($validatedData),
            'trans_currency' => 'MMK',
            'timeout_express' => '15m',
        ];

        $signKeyVal = array_merge($paymentRequest, $bizData);
        $sign = KbzPayHelper::signature(KbzPayHelper::joinKeyValue($signKeyVal));

        $paymentRequest['sign_type'] = 'SHA256';
        $paymentRequest['sign'] = $sign;
        $paymentRequest['biz_content'] = $bizData;

        return $paymentRequest;
    }

    private function getTotalAmount($validatedData)
    {
        $totalAmount = $validatedData['subTotal'] + $validatedData['tax'] + $validatedData['delivery_fee'];

        if (isset($validatedData['promocode_amount'])) {
            $totalAmount = $totalAmount - $validatedData['promocode_amount'];
        }

        return strval($totalAmount);
    }
}
