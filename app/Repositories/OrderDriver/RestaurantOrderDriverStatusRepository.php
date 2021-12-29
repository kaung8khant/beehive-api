<?php

namespace App\Repositories\OrderDriver;

use App\Events\DriverStatusChanged;
use App\Exceptions\BadRequestException;
use App\Helpers\OneSignalHelper;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\ShopOrderDriverStatus;
use App\Models\User;
use App\Repositories\OrderDriver\RestaurantOrderDriverStatusRepositoryInterface;
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
                'status' => 'pending',
            ]);
            $restaurantOrderDriverStatus = RestaurantOrderDriverStatus::create([
                'restaurant_order_driver_id' => $resOrderDriver->id,
                'status' => 'pending',
            ]);

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
        $orderDriver->status = $status;
        $orderDriver->update();

        event(new DriverStatusChanged($order, $status));

        return $domain;
    }
}
