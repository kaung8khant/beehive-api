<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    use ResponseHelper;

    public function setFavoriteRestaurant($slug)
    {
        $restaurantId = $this->getRestaurantId($slug);
        $customer = Auth::guard('customers')->user();

        try {
            $customer->favoriteRestaurants()->attach($restaurantId);
        } catch (\Exception $e) {
            return $this->generateResponse('You already set favorite this restaurant.', 409, true);
        }

        return $this->generateResponse('Success.', 200, true);
    }

    public function removeFavoriteRestaurant($slug)
    {
        $restaurantId = $this->getRestaurantId($slug);
        $customer = Auth::guard('customers')->user();
        $customer->favoriteRestaurants()->detach($restaurantId);
        return $this->generateResponse('Success.', 200, true);
    }

    public function getFavoriteRestaurants(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $customer = Auth::guard('customers')->user();

        $favoriteRestaurants = $customer->favoriteRestaurants()
            ->with(['restaurantBranches' => function ($query) use ($request) {
                $this->getBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->paginate($request->size)
            ->pluck('restaurantBranches')
            ->collapse();

        return $this->generateBranchResponse($favoriteRestaurants, 200);
    }

    public function getRecommendations(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $recommendedBranches = $this->getBranches($request)
            ->orderBy('distance', 'asc')
            ->paginate($request->size)
            ->items();

        return $this->generateBranchResponse($recommendedBranches, 200);
    }

    public function getNewArrivals(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $newArrivals = $this->getBranches($request)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateBranchResponse($newArrivals, 200);
    }

    public function getAllBranches(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantBranches = $this->getBranches($request)
            ->orderBy('distance', 'asc')
            ->paginate($request->size)
            ->items();

        return $this->generateBranchResponse($restaurantBranches, 200);
    }

    public function getOneBranch($slug)
    {
        $restaurantBranch = RestaurantBranch::with('restaurant')
            ->with('restaurant.availableTags')
            ->with('township')
            ->where('slug', $slug)
            ->where('is_enable', 1)
            ->firstOrFail();

        return $this->generateBranchResponse($restaurantBranch, 200, 'obj');
    }

    public function getAvailableMenusByBranch($slug)
    {
        $restaurantBranch = RestaurantBranch::with('restaurant')
            ->with(['availableMenus' => function ($query) {
                $query->with('restaurantCategory')
                    ->with('menuVariations')
                    ->with('menuVariations.menuVariationValues')
                    ->with('menuToppings')
                    ->where('is_enable', 1)
                    ->where('is_available', 1);
            }])
            ->where('slug', $slug)
            ->where('is_enable', 1)
            ->firstOrFail();

        $availableCategories = $restaurantBranch->availableMenus->map(function ($menu) {
            $menu->setAppends(['is_available', 'images']);
            return $menu->restaurantCategory;
        })->unique()->values();

        $restaurantBranch = $restaurantBranch->toArray();

        for ($i = 0; $i < count($availableCategories); $i++) {
            $categoryMenus = [];

            foreach ($restaurantBranch['available_menus'] as $menu) {
                if ($availableCategories[$i]['slug'] === $menu['restaurant_category']['slug'] && $menu['is_enable'] && $menu['is_available']) {
                    unset($menu['restaurant_category']);
                    array_push($categoryMenus, $menu);
                }
            }

            $availableCategories[$i]['menus'] = $categoryMenus;
        }

        unset($restaurantBranch['available_menus']);

        $availableCategories = $availableCategories->filter(function ($value) {
            return count($value['menus']) > 0;
        })->values();

        $restaurantBranch['available_categories'] = $availableCategories;

        return $this->generateResponse($restaurantBranch, 200);
    }

    public function getCategories(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $radius = config('system.restaurant_search_radius');
        $size = $request->size ? $request->size : 10;
        $page = $request->page ? $request->page : 1;

        $branchIds = DB::table('restaurant_branches')
            ->selectRaw('id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->where('is_enable', 1)
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'asc')
            ->pluck('id');

        $categoryIds = $branchIds->map(function ($branchId) {
            return DB::table('menus as m')
                ->join('restaurant_branch_menu_map as rbmm', 'rbmm.menu_id', '=', 'm.id')
                ->where('rbmm.restaurant_branch_id', $branchId)
                ->where('m.is_enable', 1)
                ->where('rbmm.is_available', 1)
                ->pluck('restaurant_category_id');
        })->collapse()->unique()->values();

        $categorizedBranches = $categoryIds->map(function ($categoryId) use ($request) {
            $category = RestaurantCategory::find($categoryId);
            $restaurantIds = Menu::where('restaurant_category_id', $categoryId)->where('is_enable', 1)->groupBy('restaurant_id')->pluck('restaurant_id');

            $category->restaurant_branches = $restaurantIds->map(function ($restaurantId) use ($request) {
                return $this->getBranches($request)
                    ->where('restaurant_id', $restaurantId)
                    ->orderBy('distance', 'asc')
                    ->get();
            })->collapse()->sortBy('distance')->values();

            return $category;
        })->filter(function ($value) {
            return count($value['restaurant_branches']) > 0;
        })->values()->slice(($page - 1) * $size, $size);

        return $this->generateBranchResponse($categorizedBranches, 200, 'arrobj');
    }

    public function getTags(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantTags = RestaurantTag::with('restaurants')
            ->with(['restaurants.restaurantBranches' => function ($query) use ($request) {
                $this->getBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)
            ->items();

        $restaurantTags = $this->getBranchesFromRestaurants($restaurantTags);
        return $this->generateBranchResponse($restaurantTags, 200, 'arrobj');
    }

    public function getByCategory(Request $request, $slug)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantCategory = RestaurantCategory::where('slug', $slug)->firstOrFail();
        $restaurantIds = Menu::where('restaurant_category_id', $restaurantCategory->id)->where('is_enable', 1)->groupBy('restaurant_id')->pluck('restaurant_id');

        $categorizedBranches = $restaurantIds->map(function ($restaurantId) use ($request) {
            return $this->getBranches($request)->where('restaurant_id', $restaurantId)->get();
        });

        $restaurantCategory->restaurant_branches = $categorizedBranches->collapse()->sortBy('distance')->values();
        return $this->generateBranchResponse($restaurantCategory, 200, 'cattag');
    }

    public function getByTag(Request $request, $slug)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantTag = RestaurantTag::with('restaurants')
            ->with(['restaurants.restaurantBranches' => function ($query) use ($request) {
                $this->getBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->where('slug', $slug)
            ->firstOrFail();

        $restaurantTag = $this->replaceRestaurantsWtihBranches($restaurantTag);
        return $this->generateBranchResponse($restaurantTag, 200, 'cattag');
    }

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function getBranches($request)
    {
        $query = RestaurantBranch::with('restaurant');
        return $this->getBranchQuery($query, $request);
    }

    private function getBranchQuery($query, $request)
    {
        $radius = config('system.restaurant_search_radius');

        return $query->with('restaurant')
            ->with('restaurant.availableTags')
            ->selectRaw('id, slug, name, address, contact_number, opening_time, closing_time, is_enable, restaurant_id, township_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->whereHas('restaurant', function ($q) {
                $q->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->having('distance', '<', $radius);
    }

    private function getBranchesFromRestaurants($items)
    {
        foreach ($items as $item) {
            $item = $this->replaceRestaurantsWtihBranches($item);
        }

        return $items;
    }

    private function replaceRestaurantsWtihBranches($data)
    {
        $branches = [];

        foreach ($data['restaurants'] as $restaurant) {
            array_push($branches, $restaurant['restaurantBranches']);
        }

        $data['restaurant_branches'] = collect($branches)->collapse()->sortBy('distance')->values();
        unset($data['restaurants']);

        return $data;
    }

    private function getRestaurantId($slug)
    {
        return Restaurant::where('slug', $slug)->firstOrFail()->id;
    }
}
