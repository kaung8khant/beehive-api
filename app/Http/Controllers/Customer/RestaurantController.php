<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;

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
            return response()->json(['message' => 'You already set favorite this restaurant.'], 409);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function removeFavoriteRestaurant($slug)
    {
        $restaurantId = $this->getRestaurantId($slug);
        $customer = Auth::guard('customers')->user();
        $customer->favoriteRestaurants()->detach($restaurantId);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function getFavoriteRestaurants(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $customer = Auth::guard('customers')->user();

        $favoriteRestaurants = $customer->favoriteRestaurants()
            ->with(['restaurantBranches' => function ($query) use ($request) {
                $this->getRestaurantBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->paginate($request->size)
            ->pluck('restaurantBranches')
            ->collapse();

        return $this->generateResponse($favoriteRestaurants, 200);
    }

    public function getRecommendations(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $recommendedBranches = $this->getRestaurantBranches($request)
            ->orderBy('distance', 'asc')
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($recommendedBranches, 200);
    }

    public function getNewArrivals(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $newArrivals = $this->getRestaurantBranches($request)
            ->latest()
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($newArrivals, 200);
    }

    public function getAllRestaurantBranches(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $restaurantBranches = $this->getRestaurantBranches($request)
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhereHas('restaurant', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                    ->orWhereHas('availableCategories', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%')
                            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%');
                    })
                    ->orWhereHas('availableTags', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->filter . '%')
                            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%');
                    });
            })
            ->orWhereHas('availableMenus', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('description_mm', 'LIKE', '%' . $request->filter . '%');
            })
            ->orderBy('distance', 'asc')
            ->paginate($request->size)
            ->items();

        return $this->generateResponse($restaurantBranches, 200);
    }

    public function getOneRestaurantBranch($slug)
    {
        $restaurantBranch = RestaurantBranch::with('restaurant')->where('slug', $slug)->firstOrFail();
        return $this->generateResponse($restaurantBranch, 200);
    }

    public function getAvailableMenusByBranch($slug)
    {
        $menus = RestaurantBranch::with('availableMenus')->where('slug', $slug)->firstOrFail();
        return $this->generateResponse($menus, 200);
    }

    public function getRestaurantCategories(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $restaurantCategories = RestaurantCategory::with('restaurants')
            ->with(['restaurants.restaurantBranches' => function ($query) use ($request) {
                $this->getRestaurantBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)
            ->items();

        $restaurantCategories = $this->getBranchesFromRestaurants($restaurantCategories);
        return $this->generateResponse($restaurantCategories, 200);
    }

    public function getRestaurantTags(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $restaurantTags = RestaurantTag::with('restaurants')
            ->with(['restaurants.restaurantBranches' => function ($query) use ($request) {
                $this->getRestaurantBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate($request->size)
            ->items();

        $restaurantTags = $this->getBranchesFromRestaurants($restaurantTags);
        return $this->generateResponse($restaurantTags, 200);
    }

    public function getRestaurantsByCategory(Request $request, $slug)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $restaurantCategory = RestaurantCategory::with('restaurants')
            ->with(['restaurants.restaurantBranches' => function ($query) use ($request) {
                $this->getRestaurantBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->where('slug', $slug)
            ->firstOrFail();

        $restaurantCategory = $this->replaceRestaurantsWtihBranches($restaurantCategory);
        return $this->generateResponse($restaurantCategory, 200);
    }

    public function getRestaurantsByTag(Request $request, $slug)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $restaurantTag = RestaurantTag::with('restaurants')
            ->with(['restaurants.restaurantBranches' => function ($query) use ($request) {
                $this->getRestaurantBranchQuery($query, $request)->orderBy('distance', 'asc');
            }])
            ->where('slug', $slug)
            ->firstOrFail();

        $restaurantTag = $this->replaceRestaurantsWtihBranches($restaurantTag);
        return $this->generateResponse($restaurantTag, 200);
    }

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function getRestaurantBranches($request)
    {
        $query = RestaurantBranch::with('restaurant');
        return $this->getRestaurantBranchQuery($query, $request);
    }

    private function getRestaurantBranchQuery($query, $request)
    {
        $radius = config('system.restaurant_search_radius');

        return $query->with('restaurant')
            ->selectRaw('id, slug, name, name_mm, address, contact_number, opening_time, closing_time, is_enable, restaurant_id, township_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
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

        $data['restaurantBranches'] = collect($branches)->collapse()->sortBy('distance')->values();
        unset($data['restaurants']);

        return $data;
    }

    private function getRestaurantId($slug)
    {
        return Restaurant::where('slug', $slug)->firstOrFail()->id;
    }
}
