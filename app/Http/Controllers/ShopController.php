<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopTag;

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
        return Shop::with('shop_categories', 'shop_tags')
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

        $shop = Shop::create($request->validate([
            'slug' => 'required|unique:shops',
            'name' => 'required|unique:shops',
            'name_mm' => 'unique:shops',
            'is_official' => 'required|boolean',
            'shop_tags' => 'required|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'shop_categories' => 'required|array',
            'shop_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]));

        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->shop_tags()->attach($shopTags);

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->shop_categories()->attach($shopCategories);

        return response()->json($shop->load(['shop_tags', 'shop_categories']), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $shop = Shop::with('shop_categories','shop_tags')->where('slug', $slug)->firstOrFail();
        return response()->json($shop, 200);
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
            'name' => [
                'required',
                Rule::unique('shops')->ignore($shop->id),
            ],
            'name_mm' => [
                Rule::unique('shops')->ignore($shop->id),
            ],
            'is_official' => 'required|boolean',
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

        return response()->json($shop->load(['shop_categories', 'shop_tags']), 201);
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
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
    * Toggle the is_enable column for shop table.
    *
    * @param  int  $slug
    * @return \Illuminate\Http\Response
    */
    public function toggleEnable($slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        $shop->is_enable = !$shop->is_enable;
        $shop->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    /**
    * Toggle the is_official column for shop table.
    *
    * @param  int  $slug
    * @return \Illuminate\Http\Response
    */
    public function toggleOfficial($slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        $shop->is_official = !$shop->is_official;
        $shop->save();
        return response()->json(['message' => 'Success.'], 200);
    }

        /**
     * add  shop Categories in Shop
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function addShopCategories(Request $request, $slug)
    {
        $shop =$request->validate([
            'shop_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]);

        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->shop_categories()->detach();
        $shop->shop_categories()->attach($shopCategories);

        return response()->json($shop->load(['shop_categories', 'shop_tags']), 201);
    }

        /**
     * remove  shop Categories in Shop
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shop  $shop
     * @return \Illuminate\Http\Response
     */
    public function removeShopCategories(Request $request, $slug)
    {
        $shop =$request->validate([
            'shop_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]);
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->shop_categories()->detach($shopCategories);

        return response()->json($shop->load(['shop_categories', 'shop_tags']), 201);
    }
}
