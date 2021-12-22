<?php

namespace App\Repositories\Restaurant\RestaurantBranch;

use App\Repositories\BaseRepositoryInterface;

interface RestaurantBranchRepositoryInterface extends BaseRepositoryInterface
{
    public function all();
}
