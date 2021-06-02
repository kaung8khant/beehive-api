<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CacheHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantOrder;
use App\Models\RestaurantTag;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class RestaurantBranchController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_branches', 'id', $request->by ? $request->by : 'desc', $request->order);

        return RestaurantBranch::with('restaurant', 'township')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('contact_number', $request->filter)
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate(
            [
                'slug' => 'required|unique:restaurant_branches',
                'name' => 'required',
                'address' => 'nullable',
                'contact_number' => 'required|phone:MM',
                'opening_time' => 'required|date_format:H:i',
                'closing_time' => 'required|date_format:H:i',
                'latitude' => 'required',
                'longitude' => 'required',
                'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
                'township_slug' => 'nullable|exists:App\Models\Township,slug',
                'is_enable' => 'required|boolean',
            ],
            [
                'contact_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['contact_number'] = PhoneNumber::make($validatedData['contact_number'], 'MM');
        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch = RestaurantBranch::create($validatedData);

        $menuIds = Menu::where('restaurant_id', $validatedData['restaurant_id'])->pluck('id');
        $restaurantBranch->availableMenus()->attach($menuIds);

        return response()->json($restaurantBranch->load('restaurant', 'township'), 201);
    }

    public function show(RestaurantBranch $restaurantBranch)
    {
        return response()->json($restaurantBranch->load('restaurant', 'township', 'township.city'), 200);
    }

    public function update(Request $request, RestaurantBranch $restaurantBranch)
    {
        $validatedData = $request->validate(
            [
                'name' => [
                    'required',
                    Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
                ],
                'address' => 'nullable',
                'contact_number' => 'required|phone:MM',
                'opening_time' => 'required|date_format:H:i',
                'closing_time' => 'required|date_format:H:i',
                'latitude' => 'required',
                'longitude' => 'required',
                'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
                'township_slug' => 'nullable|exists:App\Models\Township,slug',
                'is_enable' => 'required|boolean',
            ],
            [
                'contact_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['contact_number'] = PhoneNumber::make($validatedData['contact_number'], 'MM');
        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch->update($validatedData);
        return response()->json($restaurantBranch->load('restaurant', 'township'), 200);
    }

    public function destroy(RestaurantBranch $restaurantBranch)
    {
        $restaurantBranch->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getRestaurantId($slug)
    {
        return Restaurant::where('slug', $slug)->first()->id;
    }

    private function getTownshipId($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    public function getBranchesByRestaurant(Request $request, Restaurant $restaurant)
    {
        $sorting = CollectionHelper::getSorting('restaurant_branches', 'id', $request->by ? $request->by : 'desc', $request->order);

        return RestaurantBranch::where('restaurant_id', $restaurant->id)
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function getBranchesByTownship(Request $request, Township $township)
    {
        $sorting = CollectionHelper::getSorting('restaurant_branches', 'id', $request->by ? $request->by : 'desc', $request->order);

        return RestaurantBranch::where('township_id', $township->id)
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function toggleEnable(RestaurantBranch $restaurantBranch)
    {
        $restaurantBranch->update(['is_enable' => !$restaurantBranch->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleStatusUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\RestaurantBranch,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();
            $restaurantBranch->update(['is_enable' => $request->is_enable]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function addAvailableMenus(Request $request, RestaurantBranch $restaurantBranch)
    {
        $request->validate([
            'available_menus.*' => 'exists:App\Models\Menu,slug',
        ]);

        $availableMenus = Menu::whereIn('slug', $request->available_menus)->pluck('id');

        foreach ($availableMenus as $menuId) {
            CacheHelper::forgetCategoryIdsByBranchCache($menuId);
        }

        $restaurantBranch->availableMenus()->detach();
        $restaurantBranch->availableMenus()->attach($availableMenus);

        return response()->json($restaurantBranch->load(['availableMenus', 'restaurant', 'township']), 201);
    }

    public function removeAvailableMenus(Request $request, RestaurantBranch $restaurantBranch)
    {
        $request->validate([
            'available_menus.*' => 'exists:App\Models\Menu,slug',
        ]);

        $availableMenus = Menu::whereIn('slug', $request->available_menus)->pluck('id');

        foreach ($availableMenus as $menuId) {
            CacheHelper::forgetCategoryIdsByBranchCache($menuId);
        }

        $restaurantBranch->availableMenus()->detach($availableMenus);

        return response()->json($restaurantBranch->load(['availableMenus', 'restaurant', 'township']), 201);
    }

    public function updateWithTagsAndCategories(Request $request, RestaurantBranch $restaurantBranch)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
            ],
            'address' => 'nullable',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
            'is_enable' => 'nullable|boolean',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'available_categories' => 'nullable|array',
            'available_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch->update($validatedData);
        $restaurant = Restaurant::where('slug', $request->restaurant_slug)->firstOrFail();

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->availableTags()->detach();
        $restaurant->availableTags()->attach($restaurantTags);

        if ($request->available_categories) {
            $restaurantCategories = RestaurantCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $restaurant->availableCategories()->detach();
            $restaurant->availableCategories()->attach($restaurantCategories);
        }

        return response()->json($restaurantBranch->load('restaurant', 'township'), 200);
    }

    public function toggleAvailable(Request $request, RestaurantBranch $restaurantBranch, Menu $menu)
    {
        $validatedData = $request->validate([
            'is_available' => 'required|boolean',
        ]);

        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);

        $restaurantBranch->availableMenus()->sync([
            $menu->id => ['is_available' => $validatedData['is_available']],
        ], false);

        return response()->json(['message' => 'Success.'], 200);
    }

    public function getRestaurantBranchByCustomers(Request $request, RestaurantBranch $restaurantBranch)
    {
        $orderList = RestaurantOrder::where('restaurant_branch_id', $restaurantBranch->id)->get();

        $customerlist = [];

        foreach ($orderList as $order) {
            $customer = Customer::where('id', $order->customer_id)
                ->where(function ($query) use ($request) {
                    $query->where('email', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                        ->orWhere('slug', $request->filter);
                })
                ->first();

            $customer && array_push($customerlist, $customer);
        }

        $customerlist = collect($customerlist)->unique()->values()->all();
        $customerlist = CollectionHelper::paginate(collect($customerlist), $request->size);

        return response()->json($customerlist, 200);
    }
}
