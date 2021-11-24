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
        return 'test';
    }
}
