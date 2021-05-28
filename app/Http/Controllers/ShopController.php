<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopTag;
use App\Models\Township;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class ShopController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('shops', 'id', $request->by ? $request->by : 'desc', $request->order);

        return Shop::with('availableCategories', 'availableTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate(
            [
                'slug' => 'required|unique:shops',
                'name' => 'required|unique:shops',
                'is_enable' => 'required|boolean',
                'is_official' => 'required|boolean',
                'shop_tags' => 'nullable|array',
                'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
                'address' => 'required',
                'contact_number' => 'required|phone:MM',
                'address' => 'nullable',
                'opening_time' => 'required|date_format:H:i',
                'closing_time' => 'required|date_format:H:i',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'township_slug' => 'nullable|exists:App\Models\Township,slug',
                'image_slug' => 'nullable|exists:App\Models\File,slug',

            ],
            [
                'contact_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['contact_number'] = PhoneNumber::make($validatedData['contact_number'], 'MM');
        $townshipId = $this->getTownshipIdBySlug($request->township_slug);
        $validatedData['township_id'] = $townshipId;
        $shop = Shop::create($validatedData, $townshipId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shops', $shop->slug);
        }

        if ($request->shop_tags) {
            $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
            $shop->availableTags()->attach($shopTags);

            foreach ($shopTags as $shopTag) {
                Cache::forget('shop_ids_tag_' . $shopTag);
            }
        }

        return response()->json($shop->refresh()->load(['availableTags', 'availableCategories']), 201);
    }

    public function show(Shop $shop)
    {
        return response()->json($shop->load('availableTags', 'township', 'township.city'), 200);
    }

    public function update(Request $request, Shop $shop)
    {
        $validatedData = $request->validate(
            [
                'name' => [
                    'required',
                    Rule::unique('shops')->ignore($shop->id),
                ],
                'is_enable' => 'nullable|boolean',
                'is_official' => 'required|boolean',
                'shop_tags' => 'nullable|array',
                'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
                'address' => 'nullable',
                'contact_number' => 'required|phone:MM',
                'opening_time' => 'required|date_format:H:i',
                'closing_time' => 'required|date_format:H:i',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'township_slug' => 'nullable|exists:App\Models\Township,slug',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'contact_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['contact_number'] = PhoneNumber::make($validatedData['contact_number'], 'MM');
        $validatedData['township_id'] = $this->getTownshipIdBySlug($request->township_slug);
        $shop->update($validatedData);

        $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
        $shop->availableTags()->detach();
        $shop->availableTags()->attach($shopTags);

        foreach ($shopTags as $shopTag) {
            Cache::forget('shop_ids_tag_' . $shopTag);
        }

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shops', $shop->slug);
        }

        return response()->json($shop->load(['availableCategories', 'availableTags']), 201);
    }

    public function destroy(Shop $shop)
    {
        foreach ($shop->images as $image) {
            $this->deleteFile($image->slug);
        }

        $shop->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable(Shop $shop)
    {
        $shop->update(['is_enable' => !$shop->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleStatusUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Shop,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $shop = Shop::where('slug', $slug)->firstOrFail();
            $shop->update(['is_enable' => $request->is_enable]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function toggleOfficial(Shop $shop)
    {
        $shop->update(['is_official' => !$shop->is_official]);
        return response()->json(['message' => 'Success.'], 200);
    }

    private function getTownshipIdBySlug($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    public function getShopsByBrand(Request $request, Brand $brand)
    {
        $sorting = CollectionHelper::getSorting('shops', 'id', $request->by ? $request->by : 'desc', $request->order);

        return Shop::with('availableCategories', 'availableTags')
            ->whereHas('products', function ($q) use ($brand) {
                $q->where('brand_id', $brand->id);
            })
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function getCustomersByShop(Request $request, $slug)
    {
        $shop = Shop::where('slug', $slug)->firstOrFail();

        $orderList = ShopOrder::whereHas('vendors', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id);
        })->get();

        $customerlist = [];

        foreach ($orderList as $order) {
            $customer = Customer::where('id', $order->customer_id)->where(function ($query) use ($request) {
                $query->where('email', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })->first();
            $customer && array_push($customerlist, $customer);
        }

        $customerlist = collect($customerlist)->unique()->values()->all();
        $customerlist = CollectionHelper::paginate(collect($customerlist), $request->size);

        return response()->json($customerlist, 200);
    }
}
