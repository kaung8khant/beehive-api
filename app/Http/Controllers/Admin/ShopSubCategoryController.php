<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopSubCategory\ShopSubCategoryRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class ShopSubCategoryController extends Controller
{
    private $subCategoryRepository;

    public function __construct(ShopSubCategoryRepositoryInterface $subCategoryRepository)
    {
        $this->subCategoryRepository = $subCategoryRepository;
    }

    public function index()
    {
        $subCategories = $this->subCategoryRepository->all();
        $this->optimizeSubCategories($subCategories);
        return CollectionHelper::removePaginateLinks($subCategories);
    }

    public function show($slug)
    {
        return $this->subCategoryRepository->find($slug)->load(['shopCategory']);
    }

    public function store()
    {
        $validatedData = self::validateCreate();
        $validatedData['shop_category_id'] = $this->subCategoryRepository->getShopCategoryIdBySlug(request('shop_category_slug'));

        try {
            $shopCategory = $this->subCategoryRepository->create($validatedData)->refresh()->load(['shopCategory']);
            return response()->json($shopCategory, 201);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken for this category.');
        }
    }

    public function update($slug)
    {
        $validatedData = self::validateUpdate($slug);
        $validatedData['shop_category_id'] = $this->subCategoryRepository->getShopCategoryIdBySlug(request('shop_category_slug'));

        try {
            $subCategory = $this->subCategoryRepository->update($slug, $validatedData);
        } catch (QueryException $e) {
            return ResponseHelper::generateValidateError('code', 'The code has already been taken for this category.');
        }

        // Update the category ids of related products
        foreach ($subCategory->products as $product) {
            $product->update([
                'shop_category_id' => $validatedData['shop_category_id'],
            ]);
        }

        return $subCategory->load(['shopCategory'])->unsetRelation('products');
    }

    public function destroy($slug)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        return $this->subCategoryRepository->delete($slug);
    }

    public function getSubCategoriesByCategory($slug)
    {
        $subCategories = $this->subCategoryRepository->getAllByShopCategory($slug);
        $this->optimizeSubCategories($subCategories);
        return CollectionHelper::removePaginateLinks($subCategories);
    }

    private function optimizeSubCategories($subCategories)
    {
        $subCategories->load(['shopCategory' => function ($query) {
            $query->select('id', 'slug', 'name')->get();
        }]);

        foreach ($subCategories as $subCategory) {
            $subCategory->makeHidden(['created_by', 'updated_by']);
            $subCategory->shopCategory->setAppends([]);
        }
    }

    public function updateSearchIndex($slug)
    {
        return $this->subCategoryRepository->update($slug, request()->validate([
            'search_index' => 'required|numeric',
        ]));
    }

    private static function validateCreate()
    {
        request()->merge(['slug' => StringHelper::generateUniqueSlug()]);

        return request()->validate([
            'code' => 'required|size:2',
            'slug' => 'required|unique:shop_sub_categories',
            'name' => 'required|unique:shop_sub_categories',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);
    }

    private static function validateUpdate($slug)
    {
        return request()->validate([
            'code' => 'required|size:2',
            'name' => [
                'required',
                Rule::unique('shop_sub_categories')->ignore($slug, 'slug'),
            ],
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);
    }
}
