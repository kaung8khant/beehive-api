<?php

namespace App\Repositories\Shop\Product;

use App\Models\Product;
use App\Repositories\BaseRepository;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model, 'shop_sub_categories');
    }

    public function all()
    {
        if (request('filter')) {
            return $this->model->search(request('filter'))->paginate(10);
        } else {
            return $this->model->orderBy('search_index', 'desc')->orderBy('id', 'desc')->paginate(10);
        }
    }
}
