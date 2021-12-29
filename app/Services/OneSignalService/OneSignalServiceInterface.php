<?php

namespace App\Services\OneSignalService;


interface OneSignalServiceInterface
{
    public function prepareNotification($request);

    public function sendPush($fields);
}
