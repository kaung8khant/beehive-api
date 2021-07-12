<?php

namespace App\Services;

abstract class MessagingService
{
    protected function removeEmoji()
    {
    }

    abstract public function sendMessage($phoneNumber, $text);
}
