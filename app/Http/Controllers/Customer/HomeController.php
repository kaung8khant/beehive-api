<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use App\Models\RestaurantBranch;
use App\Models\Product;

class HomeController extends Controller
{
    use ResponseHelper;

    public function getSuggestions(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $result = [
            'restaurant_branches' => $this->getRestaurantBranches($request)->inRandomOrder()->limit(10)->get(),
            'products' => $this->getRandomProducts(),
        ];

        return $this->generateResponse($result, 200);
    }

    public function getNewArrivals(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $result = [
            'restaurant_branches' => $this->getRestaurantBranches($request)->latest()->limit(10)->get(),
            'products' => $this->getNewProducts(),
        ];

        return $this->generateResponse($result, 200);
    }

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function getRestaurantBranches($request, $suggestion = FALSE)
    {
        $radius = config('system.restaurant_search_radius');

        return RestaurantBranch::with('restaurant')
            ->selectRaw('id, slug, name, name_mm, address, contact_number, opening_time, closing_time, is_enable, restaurant_id, township_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->where('is_enable', 1)
            ->having('distance', '<', $radius);
    }

    private function getRandomProducts()
    {
        return Product::with('shop')
            ->where('is_enable', 1)
            ->inRandomOrder()
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
