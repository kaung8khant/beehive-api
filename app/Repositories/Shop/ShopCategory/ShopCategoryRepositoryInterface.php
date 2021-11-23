<?php

namespace App\Repositories\Shop\ShopCategory;

use App\Repositories\BaseRepositoryInterface;

interface ShopCategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function all();

    public function getAllByShop($slug);

    public function getAllByMainCategory($slug);

    public function getMainCategoryIdBySlug($slug);

    public function getShopIdBySlug($slug);
}
