<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RestaurantTag;

class RestaurantTagController extends Controller
{
    public function index(Request $request)
    {
        return RestaurantTag::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function getRestaurantsByTag($slug)
    {
        $restaurantTag = RestaurantTag::where('slug', $slug)->firstOrFail();
        return $restaurantTag->restaurants()->paginate(10);
    }
}
