<?php

namespace App\Services\MessageService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class BoomSmsService extends MessagingService
{
    public function sendMessage($phoneNumber, $text)
    {
        try {
            $client = new Client();

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
            $response['status'] = 'Success';

            return $response;
        } catch (RequestException $e) {
            throw $e;
        }
    }
}
