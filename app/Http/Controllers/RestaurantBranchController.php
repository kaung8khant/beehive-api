<?php

namespace App\Http\Controllers;

use App\Models\RestaurantBranch;
use Illuminate\Http\Request;

class RestaurantBranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter=$request->filter;
        return RestaurantBranch::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('contact_number', $filter)
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

        $restaurantBranch = RestaurantBranch::create($request->validate([
            'slug' => 'required|unique:restaurant_branches',
            'name' => 'required|unique:restaurant_branches',
            'name_mm'=>'unique:restaurant_branches',
            'contact_number'=>'required|unique:restaurant_branches',
            'opening_time'=>'required',
            'closing_time'=>'required',
            'latitude'=>'required',
            'longitude'=>'required',
            'township_id' => 'required|exists:App\Models\Township,id',
            'restaurant_id' => 'required|exists:App\Models\Restaurant,id',
            'enable'=> 'required|boolean:restaurant_branches',
        ]));

        return response()->json($restaurantBranch, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestaurantBranch  $restaurantBranch
     * @return \Illuminate\Http\Response
     */
    public function show(RestaurantBranch $restaurantBranch)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RestaurantBranch  $restaurantBranch
     * @return \Illuminate\Http\Response
     */
    public function edit(RestaurantBranch $restaurantBranch)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RestaurantBranch  $restaurantBranch
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RestaurantBranch $restaurantBranch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestaurantBranch  $restaurantBranch
     * @return \Illuminate\Http\Response
     */
    public function destroy(RestaurantBranch $restaurantBranch)
    {
        //
    }
}
