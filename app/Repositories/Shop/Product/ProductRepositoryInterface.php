<?php

namespace App\Repositories\Shop\Product;

use App\Repositories\BaseRepositoryInterface;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function all();
}
