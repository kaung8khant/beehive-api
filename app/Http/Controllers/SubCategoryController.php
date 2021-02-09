<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

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
        $filter = $request->filter;
        return SubCategory::with('shop_category')->where('name', 'LIKE', '%' . $filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
            ->orWhere('slug', $filter)->paginate(10);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function getSubCategoriesByCategory($slug)
    {
        return SubCategory::whereHas('shop_category', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->paginate(10);
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

        $subCategory = SubCategory::create($request->validate([
            'name' => 'required|unique:sub_categories',
            'slug' => 'required|unique:sub_categories',
            'shop_category_id' => 'required|exists:App\Models\ShopCategory,id',
        ]));

        return response()->json($subCategory, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(SubCategory::with('shop_category')->where('slug', $slug)->firstOrFail(), 200);
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

        $subCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('sub_categories')->ignore($subCategory->id),
            ],
            'shop_category_id' => 'required|exists:App\Models\ShopCategory,id',
        ]));

        return response()->json($subCategory, 200);
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
}
