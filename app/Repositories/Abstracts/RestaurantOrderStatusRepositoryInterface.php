<?php

namespace App\Repositories\Abstracts;

use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatus;

interface RestaurantOrderStatusRepositoryInterface
{
    public function createStatus(RestaurantOrder $restaurantOrder): ?RestaurantOrderStatus;

    public function changeStatus(RestaurantOrder $restaurantOrder, $status): ?RestaurantOrderStatus;
}
