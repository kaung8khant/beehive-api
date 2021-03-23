<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

trait SmsHelper
{
    public static function send($phoneNumber, $text)
    {
        try {
            $client = new Client();
            Log::debug("api_key: " . config('system.boomsms_api_key'));
            Log::debug("phoneNumber: " . $phoneNumber);
            Log::debug("text: " . $text);
            $response = $client->post(
                'https://boomsms.net/api/sms/json',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . config('system.boomsms_api_key'),
                    ],
                    'form_params' => [
                        'from' => 'Beehive',
                        'to' => str_replace('+', '', $phoneNumber),
                        'text' => $text,
                    ],
                ]
            );

            $response = json_decode($response->getBody(), true);
            return $response;
        } catch (RequestException $e) {
            throw $e;
        }
    }
}
