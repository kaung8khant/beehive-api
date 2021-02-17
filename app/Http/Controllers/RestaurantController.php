<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter=$request->filter;
        return Restaurant::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        var_dump($request);
        $request['slug'] = $this->generateUniqueSlug();

        $restaurant = Restaurant::create($request->validate([
            'slug' => 'required|unique:restaurants',
            'name' => 'required|unique:restaurants',
            'name_mm'=>'unique:restaurants',
            'official'=> 'required|boolean:restaurants',
            'enable'=> 'required|boolean:restaurants',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestautrantTag,slug',
            'restaurant_categories'=>'required|array',
            'restaurant_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
     ]));

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurant_tags()->attach($restaurantTags);

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurant_categories()->attach($restaurantCategories);

        return response()->json($restaurant->load(['restaurant_tags','restaurant_categoires']), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Restaurant  $restaurant
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(Restaurant::where('slug', $slug)->firstOrFail(), 200);
    }

    /**
       * Update the specified resource in storage.
       *
       * @param  \Illuminate\Http\Request  $request
       * @param  \App\Models\Restaurant  $restaurant
       * @return \Illuminate\Http\Response
       */
    public function update(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $restaurant->update($request->validate([
            'name' => 'required|unique:restaurants',
            'name_mm'=>'unique:restaurants',
            'official'=> 'required|boolean:restaurants',
            'enable'=> 'required|boolean:restaurants',
            Rule::unique('restaurants')->ignore($restaurant->id),
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestautrantTag,slug',
            'restaurant_categories'=>'required|array',
            'restaurant_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]));
        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurant_tags()->detach();
        $restaurant->restaurant_tags()->attach($restaurantTags);

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurant_categories()->detach();
        $restaurant->restaurant_categories()->attach($restaurantCategories);

        return response()->json($restaurant->load(['restaurant_tags','restaurant_categories']), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Restaurant  $restaurant
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Restaurant::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
