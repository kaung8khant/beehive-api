<?php

namespace App\Listeners;

use App\Events\DriverStatusChanged;
use App\Models\RestaurantOrderStatus;
use App\Models\ShopOrder;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderVendor;
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
        Log::info($order);
        if (isset($order->vendors)) {
            $status = "";
            if ($driverStatus === 'pickUp') {
                $status = "onRoute";
            } elseif ($driverStatus === 'delivered') {
                $status = "delivered";
            }

            ShopOrder::where('id', $order->id)->update(['order_status' => $status]);

            $paymentStatus = null;

            if ($status === 'delivered') {
                $paymentStatus = 'success';
            } elseif ($status === 'cancelled') {
                $paymentStatus = 'failed';
            }

            if ($paymentStatus) {
                ShopOrder::where('id', $order->id)->update(['payment_status' => $paymentStatus]);
            }

            $shopOrderVendor = ShopOrderVendor::where('shop_order_id', $order->id);
            $shopOrderVendor->update(['order_status' => $status]);
            $shopOrderVendor = $shopOrderVendor->get();

            foreach ($shopOrderVendor as $vendor) {
                ShopOrderStatus::create([
                    'shop_order_vendor_id' => $vendor->id,
                    'status' => $status,
                ]);
            }
        } else {
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
}
