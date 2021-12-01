<?php

namespace App\Repositories\Shop\ShopSubCategory;

use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use App\Repositories\BaseRepository;

class ShopSubCategoryRepository extends BaseRepository implements ShopSubCategoryRepositoryInterface
{
    public function __construct(ShopSubCategory $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        if (request('filter')) {
            return $this->model->search(request('filter'))->paginate(10);
        } else {
            return $this->model->orderBy('search_index', 'desc')->orderBy('name', 'asc')->paginate(10);
        }
    }

    public function getAllByShopCategory($slug)
    {
        $shopCategoryId = $this->getShopCategoryIdBySlug($slug);

        if (request('filter')) {
            return $this->model->search(request('filter'))->where('shop_category_id', $shopCategoryId)->paginate(10);
        } else {
            return $this->model->where('shop_category_id', $shopCategoryId)->orderBy('search_index', 'desc')->orderBy('name', 'asc')->paginate(10);
        }
    }

    public function getShopCategoryIdBySlug($slug)
    {
        return ShopCategory::where('slug', $slug)->firstOrFail()->id;
    }
}
