<?php

namespace App\Repositories;

use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatus;
use App\Repositories\Abstracts\RestaurantOrderStatusRepositoryInterface;

class RestaurantOrderStatusRepository implements RestaurantOrderStatusRepositoryInterface
{
    private $model;

    public function __construct(RestaurantOrderStatus $model)
    {
        $this->model = $model;
    }

    public function createStatus(RestaurantOrder $restaurantOrder): ?RestaurantOrderStatus
    {
        return null;
    }

    public function changeStatus(RestaurantOrder $restaurantOrder, $status): ?RestaurantOrderStatus
    {
        return null;
    }
}
