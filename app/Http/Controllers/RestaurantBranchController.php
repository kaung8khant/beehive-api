<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;
use App\Models\Restaurant;
use App\Models\Township;

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
        return RestaurantBranch::with('restaurant', 'township')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('contact_number', $request->filter)
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

        $validatedData = $request->validate([
            'slug' => 'required|unique:restaurant_branches',
            'name' => 'required|unique:restaurant_branches',
            'name_mm' => 'nullable|unique:restaurant_branches',
            'address' => 'required',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'township_slug' => 'required|exists:App\Models\Township,slug',
            'is_enable' => 'required|boolean',
        ]);

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch = RestaurantBranch::create($validatedData);
        return response()->json($restaurantBranch->load('restaurant', 'township'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestaurantBranch  $restaurantBranch
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $restaurantBranch = RestaurantBranch::with('restaurant', 'township')->where('slug', $slug)->firstOrFail();
        return response()->json($restaurantBranch, 200);
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

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
            ],
            'name_mm' => [
                Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
            ],
            'address' => 'required',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'township_slug' => 'required|exists:App\Models\Township,slug',
            'is_enable' => 'required|boolean',
        ]);

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch->update($validatedData);
        return response()->json($restaurantBranch->load('restaurant', 'township'), 200);
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

    private function getRestaurantId($slug)
    {
        return Restaurant::where('slug', $slug)->first()->id;
    }

    private function getTownshipId($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    /**
     * Display a listing of the restaurant branches by one restaurant.
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function getBranchesByRestaurant(Request $request, $slug)
    {
        return RestaurantBranch::whereHas('restaurant', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter .'%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
     * Display a listing of the restaurant branches by one township.
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function getBranchesByTownship(Request $request, $slug)
    {
        return RestaurantBranch::whereHas('township', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter .'%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
     * Toggle the is_enable column for restaurant_branches table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function toggleEnable($slug)
    {
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();
        $restaurantBranch->is_enable = !$restaurantBranch->is_enable;
        $restaurantBranch->save();
        return response()->json(['message' => 'Success.'], 200);
    }
}
