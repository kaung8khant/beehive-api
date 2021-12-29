<?php

namespace App\Services\OneSignalService;

use App\Services\OneSignalService\OneSignalServiceInterface;
use App\Services\OneSignalService\OneSignalRequest;
use Illuminate\Support\Facades\Log;

class NotificationService implements NotificationServiceInterface
{
    protected $oneSignalService;

    public function __construct(OneSignalServiceInterface $OneSignalServiceInterface)
    {

        $this->oneSignalService = $OneSignalServiceInterface;
    }

    public function sendDriverNotification($drivers, $message, $order, $type = "driver_order_update")
    {
        $request = new OneSignalRequest('driver', json_encode($drivers), $message);

        $request->data = $this->preparePushData($order, $type);
        $request->url = 'job?&slug=' . $order->slug . '&price=' . $order->total_amount . '&invoice_id=' . $order->invoice_id;

        $this->oneSignalService->sendPush($request);
    }

    public function sendUserNotification($users, $message, $title, $data, $delay, $type = "alert")
    {
        $request = new OneSignalRequest('user', $users, $message);
        $request->data = $this->preparePushData($data, $type);
        $request->title = $title;
        $request->send_after = $delay;

        if ($type == "restaruant_order") {
            $request->url = "restaurants/orders/" . $order->slug;
        } else if ($type == "shop_order") {
            $request->url = "shops/orders/" . $order->slug;
        }
        $response = $this->oneSignalService->sendPush($request);

        if (isset($response['errors'])) {
            return false;
        }

        return true;
    }
    public function sendVendorPushNotifications($users, $order, $message = null, $type)
    {
        $request = new OneSignalRequest('vendor', $users, $message);
        $request->url = 'status?&slug=' . $order['slug'] . '&orderStatus=' . $order['order_status'];
        $request->data = $this->preparePushData($order, $type);

        $this->oneSignalService->sendPush($request);
    }

    public function sendAdminPushNotifications($users, $data, $message = null)
    {

        $request = new OneSignalRequest('admin', $users, $message);

        $request->data = $data;

        $this->oneSignalService->sendPush($request);
    }

    public function sendDriverOrderUpdateNoti($users, $order, $message = null, $type = 'shop_order')
    {

        $request = new OneSignalRequest('driver', $users, $message);
        $request->url = 'job?&slug=' . $order['slug'] . '&orderStatus=' . $order['order_status'];
        $request->data = $this->preparePushData($order, $type);

        $this->oneSignalService->sendPush($request);
    }


    private function preparePushData($order, $type)
    {
        unset($order['created_by']);
        unset($order['updated_by']);
        unset($order['shop_order_items']);

        return [
            'type' => $type,
            'body' => $order,
        ];
    }
}
