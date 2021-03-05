<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use App\Models\RestaurantBranch;
use App\Models\Product;
use App\Models\Menu;

class HomeController extends Controller
{
    use ResponseHelper;

    public function getSuggestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $result = [
            'restaurant_branches' => $this->getSuggestionRestaurantBranches($request),
            'products' => $this->getRandomProducts(),
        ];

        return $this->generateResponse($result, 200);
    }

    public function getNewArrivals()
    {
        $result = [
            'menus' => $this->getNewMenus(),
            'products' => $this->getNewProducts(),
        ];

        return $this->generateResponse($result, 200);
    }

    private function getSuggestionRestaurantBranches($request)
    {
        $radius = config('system.restaurant_search_radius');

        return RestaurantBranch::with('restaurant')
            ->selectRaw('id, slug, name, name_mm, address, contact_number, opening_time, closing_time, is_enable, restaurant_id, township_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->where('is_enable', 1)
            ->having('distance', '<', $radius)
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }

    private function getRandomProducts()
    {
        return Product::with('shop')
            ->where('is_enable', 1)
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }

    private function getNewMenus()
    {
        return Menu::with('restaurant')
            ->where('is_enable', 1)
            ->latest()
            ->limit(10)
            ->get();
    }

    private function getNewProducts()
    {
        return Product::with('shop')
            ->where('is_enable', 1)
            ->latest()
            ->limit(10)
            ->get();
    }
}
