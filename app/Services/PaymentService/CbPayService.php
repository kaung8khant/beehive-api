<?php

namespace App\Services\PaymentService;

use App\Exceptions\ServerException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CbPayService extends PaymentService
{
    public function createTransaction($validatedData, $orderType)
    {
        $paymentRequest = $this->buildPaymentRequest($validatedData, $orderType);

        try {
            $client = new Client();

            $response = $client->post(
                config('payment.cb_pay.generate_url'),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authen-Token' => config('payment.cb_pay.token'),
                    ],
                    'json' => $paymentRequest,
                    'verify' => false,
                ]
            );

            $response = json_decode($response->getBody(), true);

            if (!$response || $response['code'] != '0000') {
                throw new ServerException('Error connecting to CB Pay service.');
            }

            return $response;
        } catch (RequestException $e) {
            throw new ServerException('Error connecting to CB Pay service.');
        }
    }

    private function buildPaymentRequest($validatedData, $orderType)
    {
        $paymentRequest = [
            'reqId' => $orderType . '_' . $validatedData['slug'] . '_' . random_int(1000, 9999),
            'merId' => config('payment.cb_pay.merch_id'),
            'subMerId' => config('payment.cb_pay.sub_merch_id'),
            'terminalId' => config('payment.cb_pay.terminal_id'),
            'transAmount' => isset($validatedData['totalAmount']) ? strval($validatedData['totalAmount']) : $this->getTotalAmount($validatedData),
            'transCurrency' => 'MMK',
            'ref1' => $orderType,
            'ref2' => $validatedData['slug'],
        ];

        return $paymentRequest;
    }

    private function getTotalAmount($validatedData)
    {
        $totalAmount = $validatedData['subTotal'] + $validatedData['tax'];

        if (isset($validatedData['promocode_amount'])) {
            $totalAmount = $totalAmount - $validatedData['promocode_amount'];
        }

        return strval($totalAmount);
    }

    public function checkTransaction($transactionRequest)
    {
        try {
            $client = new Client();

            $response = $client->post(
                config('payment.cb_pay.transaction_url'),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authen-Token' => config('payment.cb_pay.token'),
                    ],
                    'json' => $transactionRequest,
                    'verify' => false,
                ]
            );

            $response = json_decode($response->getBody(), true);

            if (!$response || $response['code'] != '0000') {
                throw new ServerException('Error connecting to CB Pay service.');
            }

            return $response;
        } catch (RequestException $e) {
            throw new ServerException('Error connecting to CB Pay service.');
        }
    }
}
