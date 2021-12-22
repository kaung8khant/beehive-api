<?php

namespace App\Repositories\Shop\Shop;

use App\Events\DataChanged;
use App\Models\Brand;
use App\Models\Shop;
use App\Models\ShopTag;
use App\Repositories\BaseRepository;

class ShopRepository extends BaseRepository implements ShopRepositoryInterface
{
    public function __construct(Shop $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        if (request('filter')) {
            return $this->model->search(request('filter'))->paginate(10);
        } else {
            return $this->model->orderBy('name', 'asc')->paginate(10);
        }
    }

    public function getAllByBrand($slug)
    {
        $brandId = Brand::where('slug', $slug)->firstOrFail()->id;

        $shopIds = $this->model->whereHas('products', function ($query) use ($brandId) {
            $query->where('brand_id', $brandId);
        })->pluck('id')->toArray();

        if (request('filter')) {
            return $this->model->search(request('filter'))->whereIn('id', $shopIds)->paginate(10);
        } else {
            return $this->model->orderBy('name', 'asc')->whereIn('id', $shopIds)->paginate(10);
        }
    }

    public function create(array $attributes)
    {
        $model = $this->model->create($attributes);
        DataChanged::dispatch($this->user, 'create', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        $this->updateImageIfExist($model->slug);
        $this->updateCoversIfExist($model->slug);

        if (isset($attributes['shop_tags'])) {
            $this->createShopTags($model, $attributes['shop_tags']);
        }

        return $model;
    }

    public function update($slug, array $attributes)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();
        $model->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        $this->updateImageIfExist($model->slug);
        $this->updateCoversIfExist($model->slug);

        if (isset($attributes['shop_tags'])) {
            $this->createShopTags($model, $attributes['shop_tags']);
        }

        return $model;
    }

    public function toggleOfficial($slug)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();
        $attributes = ['is_official' => !$model->is_official];
        $model->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);

        return response()->json(['message' => 'Success.'], 200);
    }

    private function createShopTags($shop, $tags)
    {
        $shopTags = ShopTag::whereIn('slug', $tags)->pluck('id');
        $shop->availableTags()->detach();
        $shop->availableTags()->attach($shopTags);

        foreach ($shopTags as $shopTag) {
            cache()->forget('shop_ids_tag_' . $shopTag);
        }
    }
}
