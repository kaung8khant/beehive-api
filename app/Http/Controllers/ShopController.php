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

    public function index(Request $request)
    {
        return Shop::with('availableCategories', 'shopTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

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
        $shop->shopTags()->attach($shopTags);

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->availableCategories()->attach($shopCategories);

        return response()->json($shop->refresh()->load(['shopTags', 'availableCategories']), 201);
    }

    public function show($slug)
    {
        $shop = Shop::with('availableCategories', 'shopTags')->where('slug', $slug)->firstOrFail();
        return response()->json($shop, 200);
    }

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
        $shop->shopTags()->detach();
        $shop->shopTags()->attach($shopTags);

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->availableCategories()->detach();
        $shop->availableCategories()->attach($shopCategories);

        return response()->json($shop->load(['availableCategories', 'shopTags']), 201);
    }

    public function destroy($slug)
    {
        Shop::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable($slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        $shop->is_enable = !$shop->is_enable;
        $shop->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    public function toggleOfficial($slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();
        $shop->is_official = !$shop->is_official;
        $shop->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    public function addShopCategories(Request $request, $slug)
    {
        $shop =$request->validate([
            'shop_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]);

        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->availableCategories()->detach();
        $shop->availableCategories()->attach($shopCategories);

        return response()->json($shop->load(['availableCategories', 'shopTags']), 201);
    }

    public function removeShopCategories(Request $request, $slug)
    {
        $shop =$request->validate([
            'shop_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]);
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shopCategories = ShopCategory::whereIn('slug', $request->shop_categories)->pluck('id');
        $shop->availableCategories()->detach($shopCategories);

        return response()->json($shop->load(['availableCategories', 'shopTags']), 201);
    }
}
