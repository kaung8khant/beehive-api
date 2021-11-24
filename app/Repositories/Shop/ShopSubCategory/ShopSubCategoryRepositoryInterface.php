<?php

namespace App\Repositories\Shop\ShopSubCategory;

use App\Repositories\BaseRepositoryInterface;

interface ShopSubCategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function all();

    public function getAllByShopCategory($slug);

    public function getShopCategoryIdBySlug($slug);
}
