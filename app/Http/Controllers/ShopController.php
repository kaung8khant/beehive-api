<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter=$request->filter;
        return Shop::with('shop_categories')
        ->where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
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

        $shop = Shop::create($request->validate([
            'slug' => 'required|unique:shops',
            'name' => 'required|unique:shops',
            'name_mm'=>'unique:shops',
            'official'=> 'required|boolean:shops',
            'enable'=> 'required|boolean:shops',
            'shop_tags' => 'required|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'shop_categories' => 'required|array',
            'shop_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]));
        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->shop_tags()->attach($shopTags);

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->shop_categories()->attach($shopCategories);


        return response()->json($shop->load(['shop_tags','shop_categories']), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(Shop::with('products', 'shop_categories')->where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shop->update($request->validate([
            'name' => 'required|unique:shops',
            'name_mm'=>'unique:shops',
            'official'=> 'required|boolean:shops',
            'enable'=> 'required|boolean:shops',
            Rule::unique('shops')->ignore($shop->id),
            'shop_tags' => 'required|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'shop_categories' => 'required|array',
            'shop_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]));

        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->shop_tags()->detach();
        $shop->shop_tags()->attach($shopTags);

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->shop_categories()->detach();
        $shop->shop_categories()->attach($shopCategories);

        return response()->json($shop->load(['shop_tags','shop_categories']), 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Shop::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
