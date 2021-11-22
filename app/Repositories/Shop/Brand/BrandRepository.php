<?php

namespace App\Repositories\Shop\Brand;

use App\Models\Brand;
use App\Repositories\BaseRepository;

class BrandRepository extends BaseRepository implements BrandRepositoryInterface
{
    public function __construct(Brand $model)
    {
        parent::__construct($model, 'brands');
    }

    public function all()
    {
        if (request('filter')) {
            return $this->model->search(request('filter'))->paginate(10);
        } else {
            return $this->model->orderBy('name', 'asc')->paginate(10);
        }
    }
}
