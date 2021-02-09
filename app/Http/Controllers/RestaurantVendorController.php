<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\RestaurantVendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantVendorController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($filter)
    {
        return RestaurantVendor::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $restaurantVendor = RestaurantVendor::create($request->validate([
            'slug' => 'required|unique:restaurant_vendors',
            'name' => 'required|unique:restaurant_vendors',
            'name_mm'=>'unique:restaurant_vendors',
            'address' => 'required|string:restaurant_vendors',
            'contactNumber'=> 'required|string:restaurant_vendors',
            'openingTime'=> 'required|timezone:restaurant_vendors',
            'closingTime'=> 'required|timezone:restaurant_vendors',
            'enable'=> 'requierd|boolean:restaurant_vendors',
            'township_id' => 'required|exists:App\Models\Township,id',
     ]));


        return response()->json($restaurantVendor, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestaurantVendor  $restaurantVendor
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        RestaurantVendor::where('slug', $slug)->firstOrFail();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RestaurantVendor  $restaurantVendor
     * @return \Illuminate\Http\Response
     */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RestaurantVendor  $restaurantVendor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RestaurantVendor $restaurantVendor, $slug)
    {
        $restaurantVendor = RestaurantVendor::where('slug', $slug)->firstOrFail();

        $restaurantVendor->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_vendors')->ignore($restaurantVendor->id),
            ],
            'township_id' => 'required|exists:App\Models\Township,id',
        ]));

        return response()->json($restaurantVendor, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestaurantVendor  $restaurantVendor
     * @return \Illuminate\Http\Response
     */
    public function destroy(RestaurantVendor $restaurantVendor, $slug)
    {
        RestaurantVendor::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
