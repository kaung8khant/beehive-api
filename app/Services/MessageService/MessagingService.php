<?php

namespace App\Services\MessageService;

abstract class MessagingService
{
    abstract public function sendMessage($phoneNumber, $text);
}
