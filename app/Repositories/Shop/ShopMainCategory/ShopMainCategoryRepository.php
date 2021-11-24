<?php

namespace App\Repositories\Shop\ShopMainCategory;

use App\Models\ShopMainCategory;
use App\Repositories\BaseRepository;

class ShopMainCategoryRepository extends BaseRepository implements ShopMainCategoryRepositoryInterface
{
    public function __construct(ShopMainCategory $model)
    {
        parent::__construct($model, 'shop_main_categories');
    }

    public function all()
    {
        return $this->model->orderBy('search_index', 'desc')->orderBy('name', 'asc')->get();
    }
}
