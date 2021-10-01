<?php

namespace App\Http\Controllers\Admin;

use App\Events\DataChanged;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Propaganistas\LaravelPhone\PhoneNumber;

class RestaurantBranchController extends Controller
{
    use StringHelper;

    private $user;

    public function __construct()
    {
        if (Auth::guard('users')->check()) {
            $this->user = Auth::guard('users')->user();
        }
    }

    /**
     * Do not delete this method. This route is only for debugging purpose.
     *
     * @author Aung Thu Moe
     */
    public function getAll()
    {
        return RestaurantBranch::with('restaurant')->get();
    }

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurant_branches', 'id', $request->by ? $request->by : 'desc', $request->order);

        return RestaurantBranch::with('restaurant')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('contact_number', $request->filter)
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateRestaurantBranch($request, true);

        $restaurantBranch = RestaurantBranch::create($validatedData);

        $menuIds = Menu::where('restaurant_id', $validatedData['restaurant_id'])->pluck('id');
        $restaurantBranch->availableMenus()->attach($menuIds);

        Cache::forget('all_restaurant_branches_restaurant_id' . $validatedData['restaurant_id']);

        return response()->json($restaurantBranch->refresh()->load('restaurant'), 201);
    }

    public function show(RestaurantBranch $restaurantBranch)
    {
        return response()->json($restaurantBranch->load('restaurant'), 200);
    }

    public function update(Request $request, RestaurantBranch $restaurantBranch)
    {
        $validatedData = $this->validateRestaurantBranch($request);

        $restaurantBranch->update($validatedData);
        Cache::forget('all_restaurant_branches_restaurant_id' . $validatedData['restaurant_id']);

        return response()->json($restaurantBranch->load('restaurant'), 200);
    }

    public function destroy(RestaurantBranch $restaurantBranch)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        Cache::forget('all_restaurant_branches_restaurant_id' . $restaurantBranch->restaurant_id);

        $restaurantBranch->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function validateRestaurantBranch($request, $slug = false, $tagsCategories = false, $branchId = null)
    {
        $rules = [
            'name' => 'required',
            'address' => 'nullable',
            'city' => 'nullable|string',
            'township' => 'nullable|string',
            'contact_number' => 'required|phone:MM',
            'notify_numbers' => 'nullable|array',
            'notify_numbers.*' => 'required|phone:MM',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'is_enable' => 'required|boolean',
            'free_delivery' => 'nullable|boolean',
            'pre_order' => 'nullable|boolean',
        ];

        if ($slug) {
            $request['slug'] = $this->generateUniqueSlug();
            $rules['slug'] = 'required|unique:restaurant_branches';
        }

        if ($tagsCategories) {
            // $rules['name'] = [
            //     'required',
            //     Rule::unique('restaurant_branches')->ignore($branchId),
            // ];
            $rules['restaurant_tags'] = 'required|array';
            $rules['restaurant_tags.*'] = 'exists:App\Models\RestaurantTag,slug';
            $rules['available_categories'] = 'nullable|array';
            $rules['available_categories.*'] = 'exists:App\Models\RestaurantCategory,slug';
        }

        $messages = [
            'contact_number.phone' => 'Invalid phone number.',
            'notify_numbers.*.phone' => 'Invalid phone number.',
        ];

        $validatedData = $request->validate($rules, $messages);
        $validatedData['contact_number'] = PhoneNumber::make($validatedData['contact_number'], 'MM');
        $validatedData['restaurant_id'] = Restaurant::where('slug', $validatedData['restaurant_slug'])->first()->id;

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

    public function getBranchesByRestaurant(Request $request, Restaurant $restaurant)
    {
        $sorting = CollectionHelper::getSorting('restaurant_branches', 'id', $request->by ? $request->by : 'desc', $request->order);

        return RestaurantBranch::where('restaurant_id', $restaurant->id)
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function toggleEnable(RestaurantBranch $restaurantBranch)
    {
        $restaurantBranch->update(['is_enable' => !$restaurantBranch->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function toggleFreeDelivery(RestaurantBranch $restaurantBranch)
    {
        $restaurantBranch->update(['free_delivery' => !$restaurantBranch->free_delivery]);
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

        $restaurantBranch->availableMenus()->detach();
        $restaurantBranch->availableMenus()->attach($availableMenus);

        foreach ($availableMenus as $menuId) {
            CacheHelper::forgetCategoryIdsByBranchCache($menuId);
        }

        return response()->json($restaurantBranch->load(['availableMenus', 'restaurant']), 201);
    }

    public function removeAvailableMenus(Request $request, RestaurantBranch $restaurantBranch)
    {
        $request->validate([
            'available_menus.*' => 'exists:App\Models\Menu,slug',
        ]);

        $availableMenus = Menu::whereIn('slug', $request->available_menus)->pluck('id');
        $restaurantBranch->availableMenus()->detach($availableMenus);

        foreach ($availableMenus as $menuId) {
            CacheHelper::forgetCategoryIdsByBranchCache($menuId);
        }

        return response()->json($restaurantBranch->load(['availableMenus', 'restaurant']), 201);
    }

    public function updateWithTagsAndCategories(Request $request, RestaurantBranch $restaurantBranch)
    {
        $validatedData = $this->validateRestaurantBranch($request, false, true, $restaurantBranch->id);

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

        return response()->json($restaurantBranch->load('restaurant'), 200);
    }

    public function toggleAvailable(Request $request, RestaurantBranch $restaurantBranch, Menu $menu)
    {
        $validatedData = $request->validate([
            'is_available' => 'required|boolean',
        ]);

        DataChanged::dispatch($this->user, 'update', 'restaurant_branch_menu_map', $menu->slug, $request->url(), 'success', $request->all());

        $restaurantBranch->availableMenus()->sync([
            $menu->id => ['is_available' => $validatedData['is_available']],
        ], false);

        CacheHelper::forgetCategoryIdsByBranchCache($menu->id);
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

    public function updateSearchIndex(Request $request, RestaurantBranch $restaurantBranch)
    {
        $validatedData = $request->validate([
            'search_index' => 'required|numeric',
        ]);

        $restaurantBranch->update($validatedData);
        return response()->json($restaurantBranch->load('restaurant'), 200);
    }
}
