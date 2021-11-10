<?php

namespace App\Repositories\Abstracts;

use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriverStatus;

interface RestaurantOrderDriverStatusRepositoryInterface
{
    public function assignDriver(RestaurantOrder $restaurantOrder, $driver): ?RestaurantOrderDriverStatus;

    public function changeStatus($order, $orderDriver, $status, $type);

    public function validateStatus($currentDriverStatus, $newDriverStatus, $currentOrderStatus): bool;

    public function checkOrderAccepted($order);
}
