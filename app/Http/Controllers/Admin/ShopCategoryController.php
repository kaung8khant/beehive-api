<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopCategory\ShopCategoryRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class ShopCategoryController extends Controller
{
    private $shopCategoryRepository;

    public function __construct(ShopCategoryRepositoryInterface $shopCategoryRepository)
    {
        $this->shopCategoryRepository = $shopCategoryRepository;
    }

    public function index()
    {
        $shopCategories = $this->shopCategoryRepository->all();
        $this->optimizeShopCategories($shopCategories);
        return CollectionHelper::removePaginateLinks($shopCategories);
    }

    public function show($slug)
    {
        return $this->shopCategoryRepository->find($slug)->load(['shopMainCategory', 'shopSubCategories']);
    }

    public function store()
    {
        $validatedData = self::validateCreate();

        if (request('shop_main_category_slug')) {
            $validatedData['shop_main_category_id'] = $this->shopCategoryRepository->getMainCategoryIdBySlug(request('shop_main_category_slug'));
        }

        try {
            $shopCategory = $this->shopCategoryRepository->create($validatedData)->refresh()->load(['shopMainCategory']);
            return response()->json($shopCategory, 201);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken for this product type.');
        }
    }

    public function update($slug)
    {
        $validatedData = self::validateUpdate($slug);

        if (request('shop_main_category_slug')) {
            $validatedData['shop_main_category_id'] = $this->shopCategoryRepository->getMainCategoryIdBySlug(request('shop_main_category_slug'));
        }

        try {
            return $this->shopCategoryRepository->update($slug, $validatedData);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken for this product type.');
        }
    }

    public function destroy($slug)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        return $this->shopCategoryRepository->delete($slug);
    }

    public function getCategoriesByShop($slug)
    {
        $shopCategories = $this->shopCategoryRepository->getAllByShop($slug);
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

    public function updateSearchIndex($slug)
    {
        return $this->shopCategoryRepository->update($slug, request()->validate([
            'search_index' => 'required|numeric',
        ]));
    }

    private static function validateCreate()
    {
        request()->merge(['slug' => StringHelper::generateUniqueSlug()]);

        return request()->validate([
            'code' => 'required|size:3',
            'slug' => 'required|unique:shop_categories',
            'name' => 'required|unique:shop_categories',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'shop_main_category_slug' => 'nullable|exists:App\Models\ShopMainCategory,slug',
        ]);
    }

    private static function validateUpdate($slug)
    {
        return request()->validate([
            'code' => 'required|size:3',
            'name' => [
                'required',
                Rule::unique('shop_categories')->ignore($slug, 'slug'),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'shop_main_category_slug' => 'nullable|exists:App\Models\ShopMainCategory,slug',
        ]);
    }
}
