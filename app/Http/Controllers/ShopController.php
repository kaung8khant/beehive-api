<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Shop;
use App\Models\ShopBranch;
use App\Models\ShopCategory;
use App\Models\ShopTag;
use App\Models\Township;

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

        $validatedData = $request->validate([
            'slug' => 'required|unique:shops',
            'name' => 'required|unique:shops',
            'name_mm' => 'nullable|unique:shops',
            'is_enable' => 'required|boolean',
            'is_official' => 'required|boolean',
            'shop_tags' => 'required|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'available_categories' => 'nullable|array',
            'available_categories.*' => 'exists:App\Models\ShopCategory,slug',
            'shop_branch' => 'required',
            'shop_branch.name' => 'required|string',
            'shop_branch.name_mm' => 'nullable|string',
            'shop_branch.address' => 'required',
            'shop_branch.contact_number' => 'required',
            'shop_branch.opening_time' => 'required|date_format:H:i',
            'shop_branch.closing_time' => 'required|date_format:H:i',
            'shop_branch.latitude' => 'required|numeric',
            'shop_branch.longitude' => 'required|numeric',
            'shop_branch.township_slug' => 'required|exists:App\Models\Township,slug',
        ]);
        $townshipId = $this->getTownshipIdBySlug($request->shop_branch['township_slug']);

        $shop = Shop::create($validatedData);
        $shopId = $shop->id;

        $this->createShopBranch($shopId, $townshipId, $validatedData['shop_branch']);

        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->shopTags()->attach($shopTags);

        if ($request->available_categories) {
            $shopCategories = ShopCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $shop->availableCategories()->attach($shopCategories);
        }
        return response()->json($shop->refresh()->load(['shopTags', 'availableCategories', 'shopBranches']), 201);
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
                'nullable',
                Rule::unique('shops')->ignore($shop->id),
            ],
            'is_enable' => 'required|boolean',
            'is_official' => 'required|boolean',
            'shop_tags' => 'required|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'available_categories' => 'nullable|array',
            'available_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]));

        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->shopTags()->detach();
        $shop->shopTags()->attach($shopTags);

        if ($request->available_categories) {
            $shopCategories = ShopCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $shop->availableCategories()->detach();
            $shop->availableCategories()->attach($shopCategories);
        }
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

    private function createShopBranch($shopId, $townshipId, $shopBranch)
    {
        $shopBranch['slug'] = $this->generateUniqueSlug();
        $shopBranch['shop_id'] = $shopId;
        $shopBranch['township_id'] = $townshipId;
        ShopBranch::create($shopBranch);
    }

    private function getTownshipIdBySlug($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    public function addShopCategories(Request $request, $slug)
    {
        $shop =$request->validate([
            'available_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]);

        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shopCategories = ShopCategory::whereIn('slug', $request->available_categories)->pluck('id');
        $shop->availableCategories()->detach();
        $shop->availableCategories()->attach($shopCategories);

        return response()->json($shop->load(['availableCategories', 'shopTags']), 201);
    }

    public function removeShopCategories(Request $request, $slug)
    {
        $shop =$request->validate([
            'available_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]);
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shopCategories = ShopCategory::whereIn('slug', $request->available_categories)->pluck('id');
        $shop->availableCategories()->detach($shopCategories);

        return response()->json($shop->load(['availableCategories', 'shopTags']), 201);
    }

}