<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Restaurant;

class RestaurantController extends Controller
{
    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function index(Request $request)
    {
        return Restaurant::with('restaurantBranches', 'restaurantCategories', 'restaurantTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function show($slug)
    {
        return Restaurant::with('restaurantCategories', 'restaurantTags')->where('slug', $slug)->first();
    }

    public function getFavoriteRestaurants()
    {
        return $this->customer->restaurants()->paginate(10);
    }

    public function setFavoriteRestaurant($slug)
    {
        $restaurantId = $this->getRestaurantId($slug);

        try {
            $this->customer->restaurants()->attach($restaurantId);
        } catch (\Exception $e) {
            return response()->json(['message' => 'You already set favorite this restaurant.'], 409);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function removeFavoriteRestaurant($slug)
    {
        $restaurantId = $this->getRestaurantId($slug);

        $this->customer->restaurants()->detach($restaurantId);
        return response()->json(['message' => 'Success.'], 200);
    }

    private function getRestaurantId($slug)
    {
        return Restaurant::where('slug', $slug)->firstOrFail()->id;
    }
}
