<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopTag;
use App\Models\Township;

class ShopController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return Shop::with('availableCategories', 'availableTags')
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
            'address' => 'required',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'township_slug' => 'required|exists:App\Models\Township,slug',
        ]);
        $townshipId = $this->getTownshipIdBySlug($request->township_slug);
        $validatedData['township_id'] = $townshipId;
        $shop = Shop::create($validatedData, $townshipId);
        $shopId = $shop->id;


        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->availableTags()->attach($shopTags);

        if ($request->available_categories) {
            $shopCategories = ShopCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $shop->availableCategories()->attach($shopCategories);
        }

        return response()->json($shop->refresh()->load(['availableTags', 'availableCategories']), 201);
    }

    public function show($slug)
    {
        $shop = Shop::with('availableCategories', 'availableTags', 'township')->where('slug', $slug)->firstOrFail();
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
            'is_enable' => 'nullable|boolean',
            'is_official' => 'required|boolean',
            'shop_tags' => 'required|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'available_categories' => 'nullable|array',
            'available_categories.*' => 'exists:App\Models\ShopCategory,slug',
            'address' => 'required',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]));

        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->availableTags()->detach();
        $shop->availableTags()->attach($shopTags);

        if ($request->available_categories) {
            $shopCategories = ShopCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $shop->availableCategories()->detach();
            $shop->availableCategories()->attach($shopCategories);
        }

        return response()->json($shop->load(['availableCategories', 'availableTags']), 201);
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

        return response()->json($shop->load(['availableCategories', 'availableTags']), 201);
    }

    public function removeShopCategories(Request $request, $slug)
    {
        $shop =$request->validate([
            'available_categories.*' => 'exists:App\Models\ShopCategory,slug',
        ]);
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $shopCategories = ShopCategory::whereIn('slug', $request->available_categories)->pluck('id');
        $shop->availableCategories()->detach($shopCategories);

        return response()->json($shop->load(['availableCategories', 'availableTags']), 201);
    }
}
