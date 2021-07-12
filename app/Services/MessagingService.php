<?php

namespace App\Services;

abstract class MessagingService
{
    abstract public function sendMessage($phoneNumber, $text);
}
