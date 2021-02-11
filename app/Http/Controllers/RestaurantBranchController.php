<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantBranchController extends Controller
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
        return RestaurantBranch::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('contact_number', $filter)
        ->orWhere('slug', $filter)->paginate(10);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function getBranchesByRestaurant($slug)
    {
        return RestaurantBranch::whereHas('restaurant', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->paginate(10);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function getBranchesByTownship($slug)
    {
        return RestaurantBranch::whereHas('township', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->paginate(10);
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

        $restaurantBranch = RestaurantBranch::create($request->validate([
            'slug' => 'required|unique:restaurant_branches',
            'name' => 'required|unique:restaurant_branches',
            'name_mm'=>'unique:restaurant_branches',
            'contact_number'=>'required|unique:restaurant_branches',
            'opening_time'=>'required|date_format:H:i',
            'closing_time'=>'required|date_format:H:i',
            'address'=>'required',
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
    public function show($slug)
    {
        return response()->json(RestaurantBranch::with(['restaurant','township'])->where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RestaurantBranch  $restaurantBranch
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $restaurantBranch->update($request->validate([
            'name' =>'required|unique:restaurant_branches',
            'name_mm'=>'unique:restaurant_branches',
            'contact_number'=>'required|unique:restaurant_branches',
            'address'=>'required',
            'opening_time'=>'required|date_format:H:i',
            'closing_time'=>'required|date_format:H:i',
            'latitude'=>'required',
            'longitude'=>'required',
            'enable'=> 'required|boolean:restaurant_branches',
             Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
            'restaurant_id' => 'required|exists:App\Models\Restaurant,id',
            'township_id' => 'required|exists:App\Models\Township,id',
        ]));

        return response()->json($restaurantBranch, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestaurantBranch  $restaurantBranch
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        RestaurantBranch::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
