<?php

namespace App\Services\OneSignalService;


interface NotificationServiceInterface
{

    public function sendDriverNotification($drivers, $message, $order);

    public function sendUserNotification($users, $message, $title, $data, $delay, $type);

    public function sendVendorPushNotifications($users, $data, $message, $type);

    public function sendAdminPushNotifications($users, $data, $message);

    public function sendDriverOrderUpdateNoti($users, $data, $message);
}
