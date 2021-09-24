<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $validatedData = $this->validateShop($request, true);

        $shop = Shop::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shops', $shop->slug);
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'shops', $shop->slug);
            }
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
        return response()->json($shop->load('availableTags'), 200);
    }

    public function update(Request $request, Shop $shop)
    {
        $validatedData = $this->validateShop($request, false, $shop->id);

        $shop->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shops', $shop->slug);
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'shops', $shop->slug);
            }
        }

        if ($request->shop_tags) {
            $shopTags = ShopTag::whereIn('slug', $request->shop_tags)->pluck('id');
            $shop->availableTags()->detach();
            $shop->availableTags()->attach($shopTags);

            foreach ($shopTags as $shopTag) {
                Cache::forget('shop_ids_tag_' . $shopTag);
            }
        }

        return response()->json($shop->load(['availableCategories', 'availableTags']), 201);
    }

    public function destroy(Shop $shop)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        foreach ($shop->images as $image) {
            $this->deleteFile($image->slug);
        }

        $shop->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function validateShop($request, $slug = false, $shopId = null)
    {
        $rules = [
            'name' => 'required|unique:shops',
            'address' => 'nullable',
            'city' => 'nullable|string',
            'township' => 'nullable|string',
            'contact_number' => 'required|phone:MM',
            'notify_numbers' => 'nullable|array',
            'notify_numbers.*' => 'required|phone:MM',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'is_official' => 'required|boolean',
            'is_enable' => 'required|boolean',
            'shop_tags' => 'nullable|array',
            'shop_tags.*' => 'exists:App\Models\ShopTag,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug'];

        if ($slug) {
            $request['slug'] = $this->generateUniqueSlug();
            $rules['slug'] = 'required|unique:shops';
        } else {
            $rules['name'] = [
                'required',
                Rule::unique('shops')->ignore($shopId),
            ];
        }

        $messages = [
            'contact_number.phone' => 'Invalid phone number.',
            'notify_numbers.*.phone' => 'Invalid phone number.',
        ];

        $validatedData = $request->validate($rules, $messages);
        $validatedData['contact_number'] = PhoneNumber::make($validatedData['contact_number'], 'MM');

        if (isset($validatedData['notify_numbers'])) {
            $validatedData['notify_numbers'] = $this->makeNotifyNumbers($validatedData['notify_numbers']);
        }

        return $validatedData;
    }

    private function makeNotifyNumbers($notifyNumbers)
    {
        $notifyNumbers = array_map(function ($notifyNumber) {
            return PhoneNumber::make($notifyNumber, 'MM');
        }, $notifyNumbers);

        return array_values(array_unique($notifyNumbers));
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
