<?php

namespace App\Http\Controllers\Customer\v3\Restaurant;

use Algolia\ScoutExtended\Facades\Algolia;
use App\Events\KeywordSearched;
use App\Helpers\AuthHelper;
use App\Helpers\CacheHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\RestaurantBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantBranchController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        if ($request->filter) {
            $restaurantIdsFromBranches = $this->searchBranches($request);
            $restaurantIdsFromMenus = $this->searchMenus($request);
            $restaurantIds = $restaurantIdsFromBranches->merge($restaurantIdsFromMenus)->unique()->values()->toArray();

            $restaurantBranches = $this->getBranches($request)->whereIn('restaurant_id', $restaurantIds)->paginate($request->size);

            KeywordSearched::dispatch(AuthHelper::getCustomerId(), $request->device_id, $request->filter, 'restaurant');
        } else {
            $restaurantBranches = $this->getBranches($request)->paginate($request->size);
        }

        $this->optimizeBranches($restaurantBranches);
        return $this->generateBranchResponse($restaurantBranches, 200, 'array', $restaurantBranches->lastPage());
    }

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function searchBranches($request)
    {
        $index = Algolia::index(RestaurantBranch::class);

        $result = $index->search($request->filter, [
            'attributesToRetrieve' => [
                'restaurant_id',
            ],
            'attributesToHighlight' => [],
            'aroundLatLng' => $request->lat . ', ' . $request->lng,
            'aroundRadius' => 10000,
            'hitsPerPage' => 1000,
            'filters' => 'is_enable:true AND is_restaurant_enable:true',
            'userToken' => AuthHelper::getCustomerSlug(),
        ]);

        return collect($result['hits'])->pluck('restaurant_id');
    }

    private function searchMenus($request)
    {
        $index = Algolia::index(Menu::class);

        $result = $index->search($request->filter, [
            'attributesToRetrieve' => [
                'restaurant_id',
            ],
            'attributesToHighlight' => [],
            'hitsPerPage' => 1000,
            'filters' => 'is_enable:true AND is_restaurant_enable:true',
            'userToken' => AuthHelper::getCustomerSlug(),
        ]);

        return collect($result['hits'])->pluck('restaurant_id');
    }

    private function getBranches($request)
    {
        return RestaurantBranch::with('restaurant')
            ->with('restaurant.availableTags')
            ->selectRaw('id, search_index, slug, name, opening_time, closing_time, is_enable, free_delivery, pre_order, restaurant_id,
            @distance := ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->selectRaw("IF(@distance < ?, true, false) AS instant_order", [CacheHelper::getRestaurantSearchRadius()])
            ->whereHas('restaurant', function ($q) {
                $q->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('search_index', 'desc')
            ->orderBy('distance', 'asc');
    }

    private function optimizeBranches($branches)
    {
        foreach ($branches as $branch) {
            $branch->restaurant->makeHidden(['created_by', 'updated_by', 'commission', 'first_order_date']);
            $branch->restaurant->availableTags->makeHidden(['created_by', 'updated_by']);
        }
    }
}
