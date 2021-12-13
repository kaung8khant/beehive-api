<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ForbiddenException;
use App\Helpers\CollectionHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\ShopCategory\ShopCategoryCreateRequest;
use App\Repositories\Shop\ShopCategory\ShopCategoryRepositoryInterface;
use App\Repositories\Shop\ShopCategory\ShopCategoryUpdateRequest;
use Illuminate\Database\QueryException;

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

    public function store(ShopCategoryCreateRequest $request)
    {
        try {
            $shopCategory = $this->shopCategoryRepository->create($request->validated())->refresh()->load(['shopMainCategory']);
            return response()->json($shopCategory, 201);
        } catch (QueryException $e) {
            if (strpos($e->getMessage(), 'shop_categories_shop_main_category_id_code_unique') !== false) {
                return ResponseHelper::generateValidateError('code', 'The code has already been taken for this product type.');
            }

            if (strpos($e->getMessage(), 'shop_categories_shop_main_category_id_name_unique') !== false) {
                return ResponseHelper::generateValidateError('name', 'The name has already been taken for this product type.');
            }
        }
    }

    public function update(ShopCategoryUpdateRequest $request, $slug)
    {
        try {
            return $this->shopCategoryRepository->update($slug, $request->validated());
        } catch (QueryException $e) {
            if (strpos($e->getMessage(), 'shop_categories_shop_main_category_id_code_unique') !== false) {
                return ResponseHelper::generateValidateError('code', 'The code has already been taken for this product type.');
            }

            if (strpos($e->getMessage(), 'shop_categories_shop_main_category_id_name_unique') !== false) {
                return ResponseHelper::generateValidateError('name', 'The name has already been taken for this product type.');
            }
        } catch (ForbiddenException $e) {
            return ResponseHelper::generateResponse($e->getMessage(), 403, true);
        }
    }

    public function destroy($slug)
    {
        if ($this->shopCategoryRepository->checkProducts($slug)) {
            return response()->json(['message' => 'Cannot delete category if there is a linked product.'], 403);
        }

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

        $shopCategories->load([
            'shopMainCategory' => fn($query) => $query->exclude(['created_by', 'updated_by']),
        ]);
    }

    public function updateSearchIndex($slug)
    {
        return $this->shopCategoryRepository->update($slug, request()->validate([
            'search_index' => 'required|numeric',
        ]));
    }
}
