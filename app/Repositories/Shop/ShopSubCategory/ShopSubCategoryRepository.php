<?php

namespace App\Repositories\Shop\ShopSubCategory;

use App\Events\DataChanged;
use App\Models\Product;
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

    public function update($slug, array $attributes)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();

        if ($this->checkProducts($slug) && $model->code && $model->code !== $attributes['code']) {
            return response()->json(['message' => 'Cannot update sub category code if there is a linked product.'], 403);
        }

        $model->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        $this->updateImageIfExist($model->slug);

        // Update the category ids of related products
        $this->updateProductCategoryIds($model->products, $attributes['shop_category_id']);
        return $model->unsetRelation('products');
    }

    public function checkProducts($slug)
    {
        $subCategoryId = $this->find($slug)->id;
        return Product::where('shop_sub_category_id', $subCategoryId)->exists();
    }

    private function updateProductCategoryIds($products, $categoryId)
    {
        foreach ($products as $product) {
            $product->update([
                'shop_category_id' => $categoryId,
            ]);
        }
    }

    public function getShopCategoryIdBySlug($slug)
    {
        return ShopCategory::where('slug', $slug)->firstOrFail()->id;
    }
}
