<?php

namespace App\Http\Controllers;

use App\Models\ShopCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter=$request->filter;
        return ShopCategory::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
    }

    /**
    * Display a listing of the shop categories by one shop.
    */
    public function getCategoriesByShop($slug)
    {
        return ShopCategory::whereHas('shops', function ($q) use ($slug) {
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
        $request['slug']=$this->generateUniqueSlug();

        $shopCategory=ShopCategory::create($request->validate(
            [
                'name'=>'required|unique:shop_categories',
                'name_mm'=>'unique:shop_categories',
                'slug'=>'required|unique:shop_categories',
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
        return response()->json(ShopCategory::with('sub_categories')->where('slug', $slug)->firstOrFail(), 200);
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
        $shopCategory=ShopCategory::where('slug', $slug)->firstOrFail();

        $shopCategory->update($request->validate([
            'name'=>'required|unique:shop_categories',
            'name_mm'=>'unique:shop_categories',
            Rule::unique('shop_categories')->ignore($shopCategory->id),
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
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
