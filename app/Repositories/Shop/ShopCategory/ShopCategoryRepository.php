<?php

namespace App\Repositories\Shop\ShopCategory;

use App\Events\DataChanged;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopMainCategory;
use App\Repositories\BaseRepository;

class ShopCategoryRepository extends BaseRepository implements ShopCategoryRepositoryInterface
{
    public function __construct(ShopCategory $model)
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

    public function getAllByShop($slug)
    {
        $categoryIds = $this->getCategoryIdsByShop($this->getShopIdBySlug($slug));

        if (request('filter')) {
            return $this->model->search(request('filter'))->whereIn('id', $categoryIds)->paginate(10);
        } else {
            return $this->model->whereIn('id', $categoryIds)->orderBy('search_index', 'desc')->orderBy('name', 'asc')->paginate(10);
        }
    }

    public function getAllByMainCategory($slug)
    {
        $mainCategoryId = $this->getMainCategoryIdBySlug($slug);

        if (request('filter')) {
            return $this->model->search(request('filter'))->where('shop_main_category_id', $mainCategoryId)->paginate(10);
        } else {
            return $this->model->where('shop_main_category_id', $mainCategoryId)->orderBy('search_index', 'desc')->orderBy('name', 'asc')->paginate(10);
        }
    }

    public function update($slug, array $attributes)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();

        if ($this->checkProducts($slug) && $model->code !== $attributes['code']) {
            return response()->json(['message' => 'Cannot update category code if there is a linked product.'], 403);
        }

        $model->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        $this->updateImageIfExist($model->slug);
        return $model;
    }

    public function checkProducts($slug)
    {
        $categoryId = $this->find($slug)->id;
        return Product::where('shop_category_id', $categoryId)->exists();
    }

    public function getMainCategoryIdBySlug($slug)
    {
        return ShopMainCategory::where('slug', $slug)->firstOrFail()->id;
    }

    public function getShopIdBySlug($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail()->id;
    }

    private function getCategoryIdsByShop($shopId)
    {
        return Product::where('shop_id', $shopId)->pluck('shop_category_id')->unique()->values()->toArray();
    }
}
