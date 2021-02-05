<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\AbstractRepository;
use App\Repositories\CategoryRepositoryInterface;

class CategoryRepository extends AbstractRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }
}
