<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopCategoryController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        if ($request->filter) {
            $shopCategories = ShopCategory::search($request->filter)->paginate(10);
        } else {
            $shopCategories = ShopCategory::orderBy('name', 'asc')->paginate(10);
        }

        $this->optimizeShopCategories($shopCategories);
        return CollectionHelper::removePaginateLinks($shopCategories);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $shopCategory = ShopCategory::create($request->validate(
            [
                'name' => 'required|unique:shop_categories',
                'slug' => 'required|unique:shop_categories',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ]
        ));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shop_categories', $shopCategory->slug);
        }

        return response()->json($shopCategory, 201);
    }

    public function show(ShopCategory $shopCategory)
    {
        return response()->json($shopCategory->load('shopSubCategories'), 200);
    }

    public function update(Request $request, ShopCategory $shopCategory)
    {
        $shopCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('shop_categories')->ignore($shopCategory->id),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shop_categories', $shopCategory->slug);
        }

        return response()->json($shopCategory, 200);
    }

    public function destroy(ShopCategory $shopCategory)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        foreach ($shopCategory->images as $image) {
            $this->deleteFile($image->slug);
        }

        $shopCategory->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function getCategoriesByShop(Request $request, Shop $shop)
    {
        $categoryIds = Product::where('shop_id', $shop->id)->pluck('shop_category_id')->unique()->values()->toArray();

        if ($request->filter) {
            $shopCategories = ShopCategory::search($request->filter)->whereIn('id', $categoryIds)->paginate(10);
        } else {
            $shopCategories = ShopCategory::whereIn('id', $categoryIds)->orderBy('name', 'asc')->paginate(10);
        }

        $this->optimizeShopCategories($shopCategories);
        return CollectionHelper::removePaginateLinks($shopCategories);
    }

    private function optimizeShopCategories($shopCategories)
    {
        $shopCategories->makeHidden(['created_by', 'updated_by']);
    }
}
