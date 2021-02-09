<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

        $restaurant = Restaurant::create($request->validate([
            'slug' => 'required|unique:restaurants',
            'name' => 'required|unique:restaurants',
            'name_mm'=>'unique:restaurants',
            'official'=> 'requierd|boolean:restaurants',
            'enable'=> 'requierd|boolean:restaurants',
     ]));


        return response()->json($restaurant, 201);
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
            'name'=>'required|unique:restaurants',
            'name_mm'=>'unique:restaurants',
            'official'=> 'requierd|boolean:restaurants',
            'enable'=> 'requierd|boolean:restaurants',
            Rule::unique('restaurants')->ignore($restaurant->id),
        ]));

        return response()->json($restaurant, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Restaurant  $restaurant
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Restaurant::where('slug', $slug)->firstOrFail()->delete;
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
