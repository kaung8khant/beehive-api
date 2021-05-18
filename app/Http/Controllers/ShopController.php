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
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class ShopController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/shops",
     *      operationId="getShopLists",
     *      tags={"Shops"},
     *      summary="Get list of shops",
     *      description="Returns list of shops",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('shops', 'id', $request->by ? $request->by : 'desc', $request->order);

        return Shop::with('availableCategories', 'availableTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/shops",
     *      operationId="storeShop",
     *      tags={"Shops"},
     *      summary="Create a Shop",
     *      description="Returns newly created shop",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created shop object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Shop")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
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
        }

        return response()->json($shop->refresh()->load(['availableTags', 'availableCategories']), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/shops/{slug}",
     *      operationId="showShop",
     *      tags={"Shops"},
     *      summary="Get One Shop",
     *      description="Returns a requested shop",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested shop",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show(Shop $shop)
    {
        return response()->json($shop->load('availableTags', 'township', 'township.city'), 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/shops/{slug}",
     *      operationId="updateShop",
     *      tags={"Shops"},
     *      summary="Update a Shop",
     *      description="Update a requested shop",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a shop",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New shop data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Shop")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
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

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shops', $shop->slug);
        }

        return response()->json($shop->load(['availableCategories', 'availableTags']), 201);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/shops/{slug}",
     *      operationId="deleteShop",
     *      tags={"Shops"},
     *      summary="Delete One Shop",
     *      description="Delete one specific shop",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested shop",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function destroy(Shop $shop)
    {
        foreach ($shop->images as $image) {
            $this->deleteFile($image->slug);
        }

        $shop->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/shops/toggle-enable/{slug}",
     *      operationId="toggleEnable",
     *      tags={"Shops"},
     *      summary="Toggle Enable a Shop",
     *      description="Toggle Enable a requested shop",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a shop",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
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

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/shops/toggle-official/{slug}",
     *      operationId="toggleOfficial",
     *      tags={"Shops"},
     *      summary="Toggle Official a Shop",
     *      description="Toggle Official a requested shop",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a shop",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function toggleOfficial(Shop $shop)
    {
        $shop->update(['is_official' => !$shop->is_official]);
        return response()->json(['message' => 'Success.'], 200);
    }

    private function getTownshipIdBySlug($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate(
            [
                'shops' => 'nullable|array',
                'shops.*.name' => 'required|unique:shops',
                'shops.*.is_enable' => 'required|boolean',
                'shops.*.is_official' => 'required|boolean',
                'shops.*.address' => 'nullable',
                'shops.*.contact_number' => 'required|phone:MM',
                'shops.*.opening_time' => 'required|date_format:H:i',
                'shops.*.closing_time' => 'required|date_format:H:i',
                'shops.*.latitude' => 'required|numeric',
                'shops.*.longitude' => 'required|numeric',
                'shops.*.township_slug' => 'nullable|exists:App\Models\Township,slug',
            ],
            [
                'shops*.contact_number.phone' => 'Invalid phone number.',
            ]
        );

        foreach ($validatedData['shops'] as $data) {
            $data['contact_number'] = PhoneNumber::make($data['contact_number'], 'MM');
            $data['township_id'] = $this->getTownshipIdBySlug($data['township_slug']);
            $data['slug'] = $this->generateUniqueSlug();
            Shop::create($data);
        }

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

    public function getShopByCustomers(Request $request, $slug)
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
