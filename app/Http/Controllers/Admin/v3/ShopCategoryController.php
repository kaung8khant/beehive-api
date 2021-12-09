<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\CollectionHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopCategory\ShopCategoryRepositoryInterface;

class ShopCategoryController extends Controller
{
    private $shopCategoryRepository;

    public function __construct(ShopCategoryRepositoryInterface $shopCategoryRepository)
    {
        $this->shopCategoryRepository = $shopCategoryRepository;
    }

    public function getCategoriesByMainCategory($slug)
    {
        $shopCategories = $this->shopCategoryRepository->getAllByMainCategory($slug);
        $this->optimizeShopCategories($shopCategories);
        return CollectionHelper::removePaginateLinks($shopCategories);
    }

    private function optimizeShopCategories($shopCategories)
    {
        $shopCategories->makeHidden(['created_by', 'updated_by']);

        $shopCategories->load(['shopMainCategory' => function ($query) {
            $query->exclude(['created_by', 'updated_by']);
        }]);
    }
}
