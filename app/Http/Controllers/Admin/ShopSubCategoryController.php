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
        $sorting = CollectionHelper::getSorting('shop_sub_categories', 'name', $request->by, $request->order);

        return ShopSubCategory::with('shopCategory')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
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
        return response()->json($shopSubCategory->load('shopCategory'), 200);
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

        return response()->json($shopSubCategory->load('shopCategory')->unsetRelation('products'), 200);
    }

    public function destroy(ShopSubCategory $shopSubCategory)
    {
        $shopSubCategory->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getShopCategoryId($slug)
    {
        return ShopCategory::where('slug', $slug)->first()->id;
    }

    public function getSubCategoriesByCategory(Request $request, ShopCategory $shopCategory)
    {
        $sorting = CollectionHelper::getSorting('shop_sub_categories', 'name', $request->by, $request->order);

        return ShopSubCategory::where('shop_category_id', $shopCategory->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }
}
