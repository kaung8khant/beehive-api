<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\ShopCategory;

class ShopCategoryController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return ShopCategory::with('subCategories')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $shopCategory = ShopCategory::create($request->validate(
            [
                'name' => 'required|unique:shop_categories',
                'name_mm' => 'nullable|unique:shop_categories',
                'slug' => 'required|unique:shop_categories',
            ]
        ));

        return response()->json($shopCategory, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ShopCategory  $shopCategory
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $shopCategory = ShopCategory::with('subCategories')->where('slug', $slug)->firstOrFail();
        return response()->json($shopCategory, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShopCategory  $shopCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $shopCategory = ShopCategory::where('slug', $slug)->firstOrFail();

        $shopCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('shop_categories')->ignore($shopCategory->id),
            ],
            'name_mm' => [
                Rule::unique('shop_categories')->ignore($shopCategory->id),
            ],
        ]));

        return response()->json($shopCategory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ShopCategory  $shopCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        ShopCategory::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    /**
     * Display a listing of the shop categories by one shop.
     */
    public function getCategoriesByShop(Request $request, $slug)
    {
        return ShopCategory::whereHas('shops', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter .'%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }
}
