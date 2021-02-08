<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\RestaurantCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantCategoryController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($filter)
    {
        return RestaurantCategory::where('name', 'LIKE', '%' . $filter . '%')
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
        $request['slug']=$this->generateUniqueSlug();

        $restaurantCategory=RestaurantCategory::create($request->validate(
            [
                'name'=>'required|unique:restaurant_categories',
                'name_mm'=>'unique:restaurant_categories',
                'slug'=>'required|unique:restaurant_categories',
            ]
        ));
        return response()->json($restaurantCategory, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestaurantCategory  $restaurantCategory
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        RestaurantCategory::where('slug', $slug)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RestaurantCategory  $restaurantCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $restaurantCategory=RestaurantCategory::where('slug', $slug)->firstOrFail();

        $restaurantCategory->update($request->validate([
            'name'=>'required|unique:restaurant_categories',
            'name_mm'=>'unique:restaurant_categories',
            Rule::unique('restaurant_categories')->ignore($restaurantCategory->id),
        ]));

        return response()->json($restaurantCategory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestaurantCategory  $restaurantCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        RestaurantCategory::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
