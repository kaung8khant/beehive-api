<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopCategoryController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('shop_categories', 'name', $request->by, $request->order);

        return ShopCategory::with('shopSubCategories')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
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
        foreach ($shopCategory->images as $image) {
            $this->deleteFile($image->slug);
        }

        $shopCategory->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function getCategoriesByShop(Request $request, $slug)
    {
        return ShopCategory::whereHas('shops', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
    }
}
