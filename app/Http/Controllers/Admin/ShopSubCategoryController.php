<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopSubCategoryController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        if ($request->filter) {
            $shopSubCategories = ShopSubCategory::search($request->filter)->paginate(10);
        } else {
            $shopSubCategories = ShopSubCategory::orderBy('search_index', 'desc')->orderBy('name', 'asc')->paginate(10);
        }

        $this->optimizeShopSubCategories($shopSubCategories);
        return CollectionHelper::removePaginateLinks($shopSubCategories);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate([
            'name' => 'required|unique:shop_sub_categories',
            'slug' => 'required|unique:shop_sub_categories',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);

        $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);

        $subCategory = ShopSubCategory::create($validatedData);
        return response()->json($subCategory->load('shopCategory'), 201);
    }

    public function show(ShopSubCategory $shopSubCategory)
    {
        return $shopSubCategory->load('shopCategory');
    }

    public function update(Request $request, ShopSubCategory $shopSubCategory)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('shop_sub_categories')->ignore($shopSubCategory->id),
            ],
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);

        $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);
        $shopSubCategory->update($validatedData);

        // Update the category ids of related products
        foreach ($shopSubCategory->products as $product) {
            $product->update([
                'shop_category_id' => $validatedData['shop_category_id'],
            ]);
        }

        return $shopSubCategory->load('shopCategory')->unsetRelation('products');
    }

    public function destroy(ShopSubCategory $shopSubCategory)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $shopSubCategory->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getShopCategoryId($slug)
    {
        return ShopCategory::where('slug', $slug)->value('id');
    }

    public function getSubCategoriesByCategory(Request $request, ShopCategory $shopCategory)
    {
        if ($request->filter) {
            $shopSubCategories = ShopSubCategory::search($request->filter)->where('shop_category_id', $shopCategory->id)->paginate(10);
        } else {
            $shopSubCategories = ShopSubCategory::where('shop_category_id', $shopCategory->id)->orderBy('search_index', 'desc')->orderBy('name', 'asc')->paginate(10);
        }

        $this->optimizeShopSubCategories($shopSubCategories);
        return CollectionHelper::removePaginateLinks($shopSubCategories);
    }

    private function optimizeShopSubCategories($subCategories)
    {
        $subCategories->load(['shopCategory' => function ($query) {
            $query->select('id', 'slug', 'name')->get();
        }]);

        foreach ($subCategories as $subCategory) {
            $subCategory->makeHidden(['created_by', 'updated_by']);
            $subCategory->shopCategory->setAppends([]);
        }
    }

    public function updateSearchIndex(Request $request, ShopSubCategory $shopSubCategory)
    {
        $shopSubCategory->update($request->validate([
            'search_index' => 'required|numeric',
        ]));

        return $shopSubCategory;
    }
}
