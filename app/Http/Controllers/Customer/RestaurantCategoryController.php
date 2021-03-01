<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RestaurantCategory;

class RestaurantCategoryController extends Controller
{
    public function index(Request $request)
    {
        return RestaurantCategory::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function getRestaurantsByCategory($slug)
    {
        $restaurantCategory = RestaurantCategory::where('slug', $slug)->firstOrFail();
        return $restaurantCategory->restaurants()->paginate(10);
    }
}
