<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\RestaurantCategory;

class RestaurantCategoryController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return RestaurantCategory::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $restaurantCategory = RestaurantCategory::create($request->validate([
            'name' => 'required|unique:restaurant_categories',
            'name_mm' => 'unique:restaurant_categories',
            'slug' => 'required|unique:restaurant_categories',
        ]));

        return response()->json($restaurantCategory, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $restaurantCategory = RestaurantCategory::where('slug', $slug)->firstOrFail();
        return response()->json($restaurantCategory, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $restaurantCategory = RestaurantCategory::where('slug', $slug)->firstOrFail();

        $restaurantCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_categories')->ignore($restaurantCategory->id),
            ],
            'name_mm' => [
                Rule::unique('restaurant_categories')->ignore($restaurantCategory->id),
            ]
        ]));

        return response()->json($restaurantCategory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        RestaurantCategory::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    /**
     * Display a listing of the restaurant categories by one restaurant.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function getCategoriesByRestaurant($slug)
    {
        return RestaurantCategory::whereHas('restaurants', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->paginate(10);
    }
}
