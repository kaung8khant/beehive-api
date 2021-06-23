<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\CacheHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    use ResponseHelper;

    public function setFavoriteRestaurant(Restaurant $restaurant)
    {
        $customer = Auth::guard('customers')->user();

        try {
            $customer->favoriteRestaurants()->attach($restaurant->id);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->generateResponse('You already set favorite this restaurant.', 409, true);
        }

        return $this->generateResponse('Success.', 200, true);
    }

    public function removeFavoriteRestaurant(Restaurant $restaurant)
    {
        $customer = Auth::guard('customers')->user();
        $customer->favoriteRestaurants()->detach($restaurant->id);
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
                RestaurantOrderHelper::getBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->paginate($request->size);

        $data = $favoriteRestaurants->pluck('restaurantBranches')->collapse();

        return $this->generateBranchResponse($data, 200, 'fav', $favoriteRestaurants->lastPage());
    }

    public function getRecommendations(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $recommendedBranches = RestaurantOrderHelper::getBranches($request)
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

        $newArrivals = RestaurantOrderHelper::getBranches($request)
            ->orderBy('id', 'desc')
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

        $restaurantBranches = RestaurantOrderHelper::getBranches($request)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhereHas('restaurant', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%');
                    })
                    ->orWhereHas('availableMenus', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%')
                            ->orWhere('description', 'LIKE', '%' . $request->filter . '%')
                            ->orWhereHas('restaurantCategory', function ($p) use ($request) {
                                $p->where('name', 'LIKE', '%' . $request->filter . '%');
                            });
                    });
            })
            ->orderBy('distance', 'asc')
            ->paginate($request->size);

        return $this->generateBranchResponse($restaurantBranches, 200, 'array', $restaurantBranches->lastPage());
    }

    public function getOneBranch(RestaurantBranch $restaurantBranch)
    {
        if (!$restaurantBranch->is_enable || !$restaurantBranch->restaurant->is_enable) {
            abort(404);
        }

        return $this->generateBranchResponse($restaurantBranch->load('restaurant', 'restaurant.availableTags'), 200, 'obj');
    }

    public function getAvailableMenusByBranch(RestaurantBranch $restaurantBranch)
    {
        if (!$restaurantBranch->is_enable || !$restaurantBranch->restaurant->is_enable) {
            abort(404);
        }

        $restaurantBranch->load([
            'restaurant',
            'availableMenus' => function ($query) {
                $query->with('restaurantCategory', 'menuVariations', 'menuVariations.menuVariationValues', 'menuVariants', 'menuToppings')
                    ->where('is_enable', 1)
                    ->where('is_available', 1)
                    ->orderBy('id', 'desc');
            },
        ]);

        $restaurantBranch->restaurant->is_favorite = $this->checkFavoriteRestaurant($restaurantBranch->restaurant->id);

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

    public function getCategorizedRestaurants(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $size = $request->size ? $request->size : 10;
        $page = $request->page ? $request->page : 1;

        $branchIds = RestaurantOrderHelper::getBranches($request)->orderBy('distance', 'asc')->pluck('id');

        $categoryIds = $branchIds->map(function ($branchId) {
            return CacheHelper::getCategoryIdsByBranch($branchId);
        })->collapse()->unique()->values();

        $categoryIdsCount = $categoryIds->count();
        $lastPage = intval($categoryIdsCount / $size);
        if ($categoryIdsCount % $size !== 0) {
            $lastPage += 1;
        }

        $categoryIds = $categoryIds->slice(($page - 1) * $size, $size)->values();

        $categorizedBranches = $categoryIds->map(function ($categoryId) use ($request) {
            $category = CacheHelper::getRestaurantCategoryById($categoryId);

            $restaurantIds = Menu::where('restaurant_category_id', $categoryId)
                ->where('is_enable', 1)
                ->whereHas('restaurant', function ($query) {
                    $query->where('is_enable', 1);
                })
                ->groupBy('restaurant_id')
                ->pluck('restaurant_id');

            $category->restaurant_branches = $restaurantIds->map(function ($restaurantId) use ($request) {
                return RestaurantOrderHelper::getBranches($request)
                    ->without('restaurant.availableTags')
                    ->where('restaurant_id', $restaurantId)
                    ->orderBy('distance', 'asc')
                    ->get();
            })->collapse()->sortBy('distance')->values();

            return $category;
        });

        return $this->generateBranchResponse($categorizedBranches, 200, 'arrobj', $lastPage);
    }

    public function getTags(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantTags = RestaurantTag::with('restaurants')
            ->with(['restaurants.restaurantBranches' => function ($query) use ($request) {
                RestaurantOrderHelper::getBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)
            ->items();

        $restaurantTags = $this->getBranchesFromRestaurants($restaurantTags)
            ->filter(function ($value) {
                return count($value['restaurant_branches']) > 0;
            })->values();

        return $this->generateBranchResponse($restaurantTags, 200, 'arrobj');
    }

    public function getByCategory(Request $request, RestaurantCategory $category)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $size = $request->size ? $request->size : 10;
        $page = $request->page ? $request->page : 1;

        $restaurantIds = Menu::where('restaurant_category_id', $category->id)
            ->where('is_enable', 1)
            ->whereHas('restaurant', function ($query) {
                $query->where('is_enable', 1);
            })
            ->groupBy('restaurant_id')
            ->pluck('restaurant_id');

        $restaurantBranches = $restaurantIds->map(function ($restaurantId) use ($request) {
            return RestaurantOrderHelper::getBranches($request)
                ->without('restaurant.availableTags')
                ->where('restaurant_id', $restaurantId)
                ->orderBy('distance', 'asc')
                ->get();
        })->collapse()->sortBy('distance')->values();

        $branchesCount = $restaurantBranches->count();
        $lastPage = intval($branchesCount / $size);
        if ($branchesCount % $size !== 0) {
            $lastPage += 1;
        }

        $category->restaurant_branches = $restaurantBranches->slice(($page - 1) * $size, $size)->values();

        return $this->generateBranchResponse($category->makeHidden(['created_by', 'updated_by']), 200, 'cattag', $lastPage);
    }

    public function getByTag(Request $request, $slug)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantTag = RestaurantTag::with(['restaurants' => function ($query) use ($request) {
            $query->with(['restaurantBranches' => function ($q) use ($request) {
                RestaurantOrderHelper::getBranchQuery($q, $request)->orderBy('distance', 'asc');
            }]);
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

    private function getBranchesFromRestaurants($items)
    {
        foreach ($items as $item) {
            $item = $this->replaceRestaurantsWtihBranches($item);
        }

        return collect($items);
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
}
