<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;

class RestaurantBranchController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $radius = config('system.restaurant_search_radius');

        // 6371000 for meter, 6371 for kilometer
        $restaurantBranches = RestaurantBranch::selectRaw('id, slug, name, name_mm, address, contact_number, opening_time, closing_time,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->where('is_enable', 1)
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        return $this->generateResponse($restaurantBranches, 200);
    }

    public function show($slug)
    {
        $restaurantBranch = RestaurantBranch::with('restaurant')->where('slug', $slug)->firstOrFail();
        return $this->generateResponse($restaurantBranch, 200);
    }

    public function getAvailableMenusByBranch($slug)
    {
        $menus = RestaurantBranch::where('slug', $slug)->firstOrFail()->availableMenus;
        return $this->generateResponse($menus, 200);
    }

    public function getRestaurantBranchesByRestaurant($slug)
    {
        $restaurantBranches = Restaurant::where('slug', $slug)->firstOrFail()->restaurantBranches;
        return $this->generateResponse($restaurantBranches, 200);
    }
}
