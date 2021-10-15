<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\CacheHelper;
use App\Helpers\GeoHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategorySorting;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use ResponseHelper;

    public function getAvailableMenusByBranch(Request $request, RestaurantBranch $restaurantBranch)
    {
        if (!$restaurantBranch->is_enable || !$restaurantBranch->restaurant->is_enable) {
            abort(404);
        }

        $restaurantBranch->load([
            'restaurant' => function ($query) {
                $query->exclude(['created_by', 'updated_by', 'commission']);
            },
            'restaurant.availableTags',
        ]);

        $restaurantBranch->makeHidden(['address', 'contact_number', 'city', 'township', 'notify_numbers', 'created_by', 'updated_by']);
        $restaurantBranch->restaurant->setAppends(['rating', 'images', 'covers']);

        $restaurantBranch->restaurant->is_favorite = $this->checkFavoriteRestaurant($restaurantBranch->restaurant->id);
        $restaurantBranch['available_categories'] = $this->getAvailableCategories($restaurantBranch->id, $restaurantBranch->restaurant->id);

        if ($request->lat && $request->lng) {
            $distance = GeoHelper::calculateDistance($request->lat, $request->lng, $restaurantBranch['latitude'], $restaurantBranch['longitude']);

            $restaurantBranch['distance'] = $distance;
            $restaurantBranch['time'] = GeoHelper::calculateDeliveryTime($distance);
            $restaurantBranch['delivery_fee'] = $restaurantBranch['free_delivery'] ? 0 : GeoHelper::calculateDeliveryFee($distance);
            $restaurantBranch['instant_order'] = $distance < CacheHelper::getRestaurantSearchRadius() ? true : false;
        }

        return $this->generateResponse($restaurantBranch, 200);
    }

    private function getAvailableCategories($branchId, $restaurantId)
    {
        $availableMenus = $this->getAvailableMenus($branchId);

        return $availableMenus->pluck('restaurantCategory')
            ->unique()
            ->map(function ($category) use ($restaurantId) {
                $searchIndex = RestaurantCategorySorting::where('restaurant_id', $restaurantId)
                    ->where('restaurant_category_id', $category->id)
                    ->value('search_index');
                $category['search_index'] = $searchIndex ? $searchIndex : 0;
                return $category;
            })
            ->sortBy([
                ['search_index', 'desc'],
                ['name', 'asc'],
            ])
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
            ->with([
                'restaurantCategory' => function ($query) {
                    $query->exclude(['created_by', 'updated_by']);
                },
                'menuVariations',
                'menuVariations.menuVariationValues',
                'menuVariants' => function ($query) {
                    $query->where('is_enable', 1);
                },
                'menuToppings' => function ($query) {
                    $query->exclude(['created_by', 'updated_by']);
                },
                'menuOptions',
                'menuOptions.options',
            ])
            ->where('is_enable', 1)
            ->whereHas('restaurantBranches', function ($query) use ($branchId) {
                $query->where('restaurant_branch_id', $branchId)
                    ->where('is_available', 1);
            })
            ->orderBy('search_index', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }
}
