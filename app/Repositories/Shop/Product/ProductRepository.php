<?php

namespace App\Repositories\Shop\Product;

use App\Events\DataChanged;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Repositories\BaseRepository;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        if (request('filter')) {
            return $this->model->search(request('filter'))->paginate(10);
        } else {
            return $this->model->orderBy('search_index', 'desc')->orderBy('id', 'desc')->paginate(10);
        }
    }

    public function getAllByShop($slug)
    {
        $shopId = $this->getShopIdBySlug($slug);

        if (request('filter')) {
            return Product::search(request('filter'))->where('shop_id', $shopId)->paginate(10);
        } else {
            return Product::where('shop_id', $shopId)->orderBy('search_index', 'desc')->orderBy('id', 'desc')->paginate(10);
        }
    }

    public function getAllByBrand($slug)
    {
        $brandId = $this->getBrandIdBySlug($slug);

        if (request('filter')) {
            return Product::search(request('filter'))->where('brand_id', $brandId)->paginate(10);
        } else {
            return Product::where('brand_id', $brandId)->orderBy('search_index', 'desc')->orderBy('id', 'desc')->paginate(10);
        }
    }

    public function getAllByCategory($slug)
    {
        $categoryId = $this->getCategoryIdBySlug($slug);

        if (request('filter')) {
            return Product::search(request('filter'))->where('shop_category_id', $categoryId)->paginate(10);
        } else {
            return Product::where('shop_category_id', $categoryId)->orderBy('search_index', 'desc')->orderBy('id', 'desc')->paginate(10);
        }
    }

    public function create(array $attributes)
    {
        $model = $this->model->create($attributes);
        DataChanged::dispatch($this->user, 'create', $this->model->getTable(), $attributes['slug'], request()->url(), 'success', $attributes);
        $this->updateImagesIfExist($model->slug);

        if (isset($attributes['product_variants'])) {
            $this->createProductVariants($model->id, $attributes['product_variants']);
        }

        return $model;
    }

    public function update($slug, array $attributes)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();
        $model->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        $this->updateImagesIfExist($model->slug);
        return $model;
    }

    public function getShopIdBySlug($slug)
    {
        return Shop::where('slug', $slug)->firstOrFail()->id;
    }

    public function getBrandIdBySlug($slug)
    {
        return Brand::where('slug', $slug)->firstOrFail()->id;
    }

    public function getCategoryIdBySlug($slug)
    {
        return ShopCategory::where('slug', $slug)->firstOrFail()->id;
    }

    private function updateImagesIfExist($slug)
    {
        if (request('image_slugs')) {
            foreach (request('image_slugs') as $imageSlug) {
                FileHelper::updateFile($imageSlug, $this->model->getTable(), $slug);
            }
        }
    }

    private function createProductVariants($productId, $productVariants)
    {
        foreach ($productVariants as $variant) {
            $variant['slug'] = StringHelper::generateUniqueSlugWithTable('product_variants');
            $variant['product_id'] = $productId;

            $variant = ProductVariant::create($variant);
            DataChanged::dispatch($this->user, 'create', $variant->getTable(), $variant['slug'], request()->url(), 'success', $variant);

            if (isset($variant['image_slug'])) {
                FileHelper::updateFile($variant['image_slug'], 'product_variants', $variant['slug']);
            }
        }
    }
}
