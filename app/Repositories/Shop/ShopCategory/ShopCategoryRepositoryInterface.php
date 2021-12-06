<?php

namespace App\Repositories\Shop\ShopCategory;

use App\Repositories\BaseRepositoryInterface;

interface ShopCategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function all();
}
