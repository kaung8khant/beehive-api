<?php

namespace App\Services\MessageService;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SlackMessagingService extends MessagingService
{
    public function sendMessage($phoneNumber, $text)
    {
        try {
            $client = new Client();

            $response = $client->post(
                config('system.slack_webhook_url'),
                [
                    'json' => [
                        'text' => "Phone Number: {$phoneNumber}, message: {$text}",
                    ],
                ]
            );

            $response = [
                'status' => 0,
                'message_id' => rand(1000000, 9999999),
                'to' => $phoneNumber,
                'message_count' => 1,
            ];

            return $response;
        } catch (RequestException $e) {
            throw $e;
        }
    }
}
