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
        return ShopMainCategory::orderBy('name', 'asc')->get();
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $shopMainCategory = ShopMainCategory::create($request->validate([
            'slug' => 'required|unique:shop_main_categories',
            'name' => 'required|unique:shop_main_categories',
        ]));

        return response()->json($shopMainCategory, 201);
    }

    public function show(ShopMainCategory $shopMainCategory)
    {
        return $shopMainCategory->load(['shopCategories' => function ($query) {
            $query->exclude(['created_by', 'updated_by'])->orderBy('name', 'asc');
        }]);
    }

    public function update(Request $request, ShopMainCategory $shopMainCategory)
    {
        $shopMainCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('shop_main_categories')->ignore($shopMainCategory->id),
            ],
        ]));

        return response()->json($shopMainCategory, 200);
    }

    public function destroy(ShopMainCategory $shopMainCategory)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $shopMainCategory->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }
}
