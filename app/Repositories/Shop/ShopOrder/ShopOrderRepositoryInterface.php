<?php

namespace App\Repositories\Shop\ShopOrder;

use App\Repositories\BaseRepositoryInterface;

interface ShopOrderRepositoryInterface extends BaseRepositoryInterface
{
    public function all();
}
