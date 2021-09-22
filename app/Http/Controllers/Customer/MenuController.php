<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\GeoHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\RestaurantBranch;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use ResponseHelper;

    public function getAvailableMenusByBranch(Request $request, RestaurantBranch $restaurantBranch)
    {
        if (!$restaurantBranch->is_enable || !$restaurantBranch->restaurant->is_enable) {
            abort(404);
        }

        $restaurantBranch->load(['restaurant' => function ($query) {
            $query->exclude(['created_by', 'updated_by']);
        }])->makeHidden(['created_by', 'updated_by']);

        $restaurantBranch->restaurant->is_favorite = $this->checkFavoriteRestaurant($restaurantBranch->restaurant->id);
        $restaurantBranch['available_categories'] = $this->getAvailableCategories($restaurantBranch->id);

        if ($request->lat && $request->lng) {
            $distance = GeoHelper::calculateDistance($request->lat, $request->lng, $restaurantBranch['latitude'], $restaurantBranch['longitude']);

            $restaurantBranch['distance'] = $distance;
            $restaurantBranch['time'] = GeoHelper::calculateDeliveryTime($distance);
            $restaurantBranch['delivery_fee'] = $restaurantBranch['free_delivery'] ? 0 : GeoHelper::calculateDeliveryFee($distance);
        }

        return $this->generateResponse($restaurantBranch, 200);
    }

    private function getAvailableCategories($branchId)
    {
        $availableMenus = $this->getAvailableMenus($branchId);

        return $availableMenus->pluck('restaurantCategory')
            ->unique()
            ->values()
            ->map(function ($category) use ($availableMenus) {
                $categoryMenus = $availableMenus->map(function ($menu) use ($category) {
                    if ($category->slug === $menu->restaurantCategory->slug) {
                        $menu->makeHidden('restaurantCategory');
                        return $menu;
                    }
                })->filter()->values();

                $category['menus'] = $categoryMenus;
                return $category;
            })
            ->filter(function ($value) {
                return count($value['menus']) > 0;
            })
            ->values();
    }

    private function getAvailableMenus($branchId)
    {
        return Menu::exclude(['created_by', 'updated_by'])
            ->with('restaurantCategory', function ($query) {
                $query->exclude(['created_by', 'updated_by']);
            })
            ->with('menuVariations', 'menuVariations.menuVariationValues', 'menuToppings', 'menuOptions', 'menuOptions.options')
            ->with('menuVariants', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->whereHas('restaurantBranches', function ($query) use ($branchId) {
                $query->where('restaurant_branch_id', $branchId)
                    ->where('is_available', 1);
            })
            ->orderBy('search_index', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

}
