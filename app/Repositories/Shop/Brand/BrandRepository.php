<?php

namespace App\Repositories\Shop\Brand;

use App\Events\DataChanged;
use App\Exceptions\ForbiddenException;
use App\Models\Brand;
use App\Models\Product;
use App\Repositories\BaseRepository;

class BrandRepository extends BaseRepository implements BrandRepositoryInterface
{
    public function __construct(Brand $model)
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

    public function update($slug, array $attributes)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();

        if ($this->checkProducts($slug) && $model->code && $model->code !== $attributes['code']) {
            throw new ForbiddenException('Cannot update brand code if there is a linked product.');
        }

        $model->update($attributes);
        DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        $this->updateImageIfExist($model->slug);
        return $model;
    }

    public function checkProducts($slug)
    {
        $brandId = $this->find($slug)->id;
        return Product::where('brand_id', $brandId)->exists();
    }
}
