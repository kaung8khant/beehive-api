<?php

namespace App\Repositories;

use App\Events\DriverStatusChanged;
use App\Exceptions\BadRequestException;
use App\Helpers\OneSignalHelper;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\ShopOrderDriverStatus;
use App\Models\User;
use App\Repositories\Abstracts\RestaurantOrderDriverStatusRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RestaurantOrderDriverStatusRepository implements RestaurantOrderDriverStatusRepositoryInterface
{
    use OneSignalHelper;

    private $model;
    private $database;

    public function __construct(RestaurantOrderDriverStatus $model, ShopOrderDriverStatus $shopModel)
    {
        $this->model = $model;
        $this->shopModel = $shopModel;
        $this->database = app('firebase.database');
    }
    public function setJobToFirebase($slug, $driver)
    {

        $order = $this->database->getReference('/orders')
            ->orderByChild('time')
        // enable the following code if you want real-time active data.
            ->startAt(Carbon::now()->subDay(1)->toDateTimeString())
            ->getSnapshot()->getValue();
        $order[$slug] = [
            'driver' => $driver,
            'time' => Carbon::now()->toDateTimeString(),
        ];
        $this->database->getReference('/orders')
            ->set($order);
    }
    public function checkOrderAccepted($order)
    {
        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact')
            ->where('slug', $order->slug)
            ->whereHas('drivers.status', function ($q) {
                $q->where('status', '!=', 'pending');
                $q->where('status', '!=', 'rejected');
            })
            ->get();
        return count($restaurantOrder) > 0;
    }
    public function assignDriver(RestaurantOrder $restaurantOrder, $driverSlug): ?RestaurantOrderDriverStatus
    {
        $driver = User::where('slug', $driverSlug)->first();
        $driverId = $driver->id;
        $resOrderDriver = RestaurantOrderDriver::where('restaurant_order_id', $restaurantOrder->id)->where('user_id', $driverId)->first();
        if (empty($resOrderDriver)) {
            $resOrderDriver = RestaurantOrderDriver::create([
                'restaurant_order_id' => $restaurantOrder->id,
                'user_id' => $driverId,
                'status' => 'pending'
            ]);
            $restaurantOrderDriverStatus = RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $resOrderDriver->id,
                'status' => 'pending',
            ]);

            //start of one signal send notification
            $request = new Request();

            $request['slugs'] = array($driverSlug);
            $request['message'] = 'You have received new order. Accept Now!';
            $request['url'] = 'hive://beehivedriver/job?&slug=' . $restaurantOrder->slug . '&price=' . $restaurantOrder->total_amount . '&invoice_id=' . $restaurantOrder->invoice_id;
            $request['android_channel_id'] = config('one-signal.android_channel_id');

            $appId = config('one-signal.admin_app_id');
            $request['data'] = ['slug' => $restaurantOrder->slug, 'price' => $restaurantOrder->total_amount, 'invoice_id' => $restaurantOrder->invoice_id];
            $fields = OneSignalHelper::prepareNotification($request, $appId);

            $response = OneSignalHelper::sendPush($fields, 'admin');
            // end of one signal send notification

            return $restaurantOrderDriverStatus;
        }
        throw new BadRequestException("This driver is already assigned to this order.");
    }

    public function changeStatus($order, $orderDriver, $status, $type)
    {
        $orderDriver->status = $status;
        $orderDriver->save();

        if ($type == "restaurant") {

            $domain = $this->model->create([
                'restaurant_order_driver_id' => $orderDriver->id,
                'status' => $status,
            ]);
        } else {
            $domain = $this->shopModel->create([
                'shop_order_driver_id' => $orderDriver->id,
                'status' => $status,
            ]);
        }

        event(new DriverStatusChanged($order, $status));

        return $domain;
    }

    public function validateStatus($currentDriverStatus, $newDriverStatus, $currentOrderStatus): bool
    {
        // if the order is pending, you can either accept or reject.
        if ($currentDriverStatus == 'pending' && ($newDriverStatus == 'accepted' || $newDriverStatus == 'rejected')) {
            return true;
        }

        // can only pickUp when the order is ready to pickUp.
        if ($newDriverStatus == 'pickUp' && $currentOrderStatus != 'pickUp') {
            throw new BadRequestException("Order is not yet ready to pick up", 401);
        }

        // if the order is already pickUp, you can only deliver now.
        if ($currentDriverStatus == 'pickUp' && $newDriverStatus != 'delivered') {
            throw new BadRequestException("Order is already pickUp, you can only deliver now", 401);
        }

        $driverStatuses = ['pending' => 0, 'accepted' => 1, 'pickUp' => 2, 'delivered' => 3, 'rejected' => 3, 'cancelled' => 3];

        $currentDriverStatusValue = $driverStatuses[$currentDriverStatus];
        $newDriverStatusValue = $driverStatuses[$newDriverStatus];

        // you cannot change the status with the same level as of the current one
        if ($currentDriverStatusValue === $newDriverStatusValue) {
            throw new BadRequestException("Order is already {$currentDriverStatus}", 401);
        }

        // status always need to be incremented.
        if ($newDriverStatusValue != $currentDriverStatusValue + 1) {
            throw new BadRequestException("Order is already {$currentDriverStatus}, you cannot {$newDriverStatus} anymore.", 401);
        }
        return true;
    }
}
