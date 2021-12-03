<?php

namespace App\Repositories\Shop\ShopMainCategory;

use App\Events\DataChanged;
use App\Models\Product;
use App\Models\ShopMainCategory;
use App\Repositories\BaseRepository;

class ShopMainCategoryRepository extends BaseRepository implements ShopMainCategoryRepositoryInterface
{
    public function __construct(ShopMainCategory $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        return $this->model->orderBy('search_index', 'desc')->orderBy('name', 'asc')->get();
    }

    public function update($slug, array $attributes)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();

        if ($this->checkProducts($slug) && $model->code && $model->code !== $attributes['code']) {
            return response()->json(['message' => 'Cannot update product type code if there is a linked product.'], 403);
        }

        $model->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        $this->updateImageIfExist($model->slug);
        return $model;
    }

    public function checkProducts($slug)
    {
        $categoryIds = $this->find($slug)->shopCategories->pluck('id');
        return Product::whereIn('shop_category_id', $categoryIds)->exists();
    }
}
