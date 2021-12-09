<?php

namespace App\Repositories\Shop\Shop;

use App\Repositories\BaseRepositoryInterface;

interface ShopRepositoryInterface extends BaseRepositoryInterface
{
    public function all();
}
