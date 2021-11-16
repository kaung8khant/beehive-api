<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\ShopMainCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopMainCategoryController extends Controller
{
    use StringHelper;

    public function index()
    {
        return ShopMainCategory::orderBy('search_index', 'desc')->orderBy('name', 'asc')->get();
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $shopMainCategory = ShopMainCategory::create($request->validate([
            'slug' => 'required|unique:shop_main_categories',
            'name' => 'required|unique:shop_main_categories',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shop_main_categories', $shopMainCategory->slug);
        }

        return response()->json($shopMainCategory, 201);
    }

    public function show(ShopMainCategory $shopMainCategory)
    {
        return $shopMainCategory;
    }

    public function update(Request $request, ShopMainCategory $shopMainCategory)
    {
        $shopMainCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('shop_main_categories')->ignore($shopMainCategory->id),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shop_main_categories', $shopMainCategory->slug);
        }

        return $shopMainCategory;
    }

    public function destroy(ShopMainCategory $shopMainCategory)
    {
        return response()->json(['message' => 'Permission denied.'], 403);
        foreach ($shopMainCategory->images as $image) {
            $this->deleteFile($image->slug);
        }
        $shopMainCategory->delete();

        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function updateSearchIndex(Request $request, ShopMainCategory $shopMainCategory)
    {
        $shopMainCategory->update($request->validate([
            'search_index' => 'required|numeric',
        ]));

        return $shopMainCategory;
    }
}
