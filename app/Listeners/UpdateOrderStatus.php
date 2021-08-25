<?php

namespace App\Listeners;

use App\Events\DriverStatusChanged;
use App\Models\RestaurantOrderStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatus
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  DriverStatusChanged  $event
     * @return void
     */
    public function handle(DriverStatusChanged $event)
    {
        $order = $event->getOrder();
        $driverStatus = $event->getDriverStatus();

        if ($driverStatus === 'pickup') {
            // call $restaurantOrderStatusRepository->create();
            RestaurantOrderStatus::create([
                'status' => 'onRoute',
                'restaurant_order_id' => $order->id,
            ]);

            $order->update(['order_status' => 'onRoute']);
        } elseif ($driverStatus === 'delivered') {
            // call $restaurantOrderStatusRepository->create();
            RestaurantOrderStatus::create([
                'status' => 'delivered',
                'restaurant_order_id' => $order->id,
            ]);
            // this should call repository.
            $order->update(['order_status' => 'delivered', 'payment_status' => 'success']);
        }
    }
}
