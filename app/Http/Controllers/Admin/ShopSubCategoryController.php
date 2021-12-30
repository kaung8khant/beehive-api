<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ForbiddenException;
use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopSubCategory\ShopSubCategoryCreateRequest;
use App\Repositories\Shop\ShopSubCategory\ShopSubCategoryRepositoryInterface;
use App\Repositories\Shop\ShopSubCategory\ShopSubCategoryUpdateRequest;
use Illuminate\Database\QueryException;

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

    public function store(ShopSubCategoryCreateRequest $request)
    {
        try {
            $shopCategory = $this->subCategoryRepository->create($request->validated())->refresh()->load(['shopCategory']);
            return response()->json($shopCategory, 201);
        } catch (QueryException $e) {
            if (strpos($e->getMessage(), 'shop_sub_categories_shop_category_id_code_unique') !== false) {
                return ResponseHelper::generateValidateError('code', 'The code has already been taken for this category.');
            }

            if (strpos($e->getMessage(), 'shop_sub_categories_shop_category_id_name_unique') !== false) {
                return ResponseHelper::generateValidateError('name', 'The name has already been taken for this category.');
            }
        }
    }

    public function update(ShopSubCategoryUpdateRequest $request, $slug)
    {
        try {
            return $this->subCategoryRepository->update($slug, $request->validated())->load(['shopCategory']);
        } catch (QueryException $e) {
            if (strpos($e->getMessage(), 'shop_sub_categories_shop_category_id_code_unique') !== false) {
                return ResponseHelper::generateValidateError('code', 'The code has already been taken for this category.');
            }

            if (strpos($e->getMessage(), 'shop_sub_categories_shop_category_id_name_unique') !== false) {
                return ResponseHelper::generateValidateError('name', 'The name has already been taken for this category.');
            }
        } catch (ForbiddenException $e) {
            return ResponseHelper::generateResponse($e->getMessage(), 403, true);
        }
    }

    public function destroy($slug)
    {
        if ($this->subCategoryRepository->checkProducts($slug)) {
            return response()->json(['message' => 'Cannot delete sub category if there is a linked product.'], 403);
        }

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
        $subCategories->load([
            'shopCategory' => fn ($query) => $query->select('id', 'slug', 'name', 'code')->get(),
        ]);

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
}
