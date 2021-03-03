<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\ShopCategory;
use App\Models\SubCategory;

class SubCategoryController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return SubCategory::with('shopCategory')
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

        $validatedData = $request->validate([
            'name' => 'required|unique:sub_categories',
            'name_mm' => 'nullable|unique:sub_categories',
            'slug' => 'required|unique:sub_categories',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);

        $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);

        $subCategory = SubCategory::create($validatedData);
        return response()->json($subCategory->load('shopCategory'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $subCategory = SubCategory::with('shopCategory')->where('slug', $slug)->firstOrFail();
        return response()->json($subCategory, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $subCategory = SubCategory::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('sub_categories')->ignore($subCategory->id),
            ],
            'name_mm' => [
                'nullable',
                Rule::unique('sub_categories')->ignore($subCategory->id),
            ],
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);

        $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);
        $subCategory->update($validatedData);

        // Update the category ids of related products
        foreach ($subCategory->products as $product) {
            $product->update([
                'shop_category_id' => $validatedData['shop_category_id']
            ]);
        }

        return response()->json($subCategory->load('shopCategory')->unsetRelation('products'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        SubCategory::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getShopCategoryId($slug)
    {
        return ShopCategory::where('slug', $slug)->first()->id;
    }

    /**
    * Display a listing of the sub categories by one shop category.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  string  $slug
    * @return \Illuminate\Http\Response
    */
    public function getSubCategoriesByCategory(Request $request, $slug)
    {
        return SubCategory::whereHas('shopCategory', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter .'%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }
}
