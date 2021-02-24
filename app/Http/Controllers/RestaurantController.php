<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;

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
        return Restaurant::with('restaurant_categories', 'restaurant_tags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
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
        $branchSlug = $request->restaurant_branch;
        $branchSlug['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $restaurant = Restaurant::create($validatedData);
        $restaurantId= $restaurant->id;

        $this->createRestaurantBranch($restaurantId, $validatedData['restaurant_branch']);

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurant_tags()->attach($restaurantTags);

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurant_categories()->attach($restaurantCategories);

        return response()->json($restaurant->load('restaurant_tags', 'restaurant_categories', 'restaurant_branches'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Restaurant  $restaurant
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $restaurant = Restaurant::with('restaurant_categories', 'restaurant_tags')->where('slug', $slug)->firstOrFail();
        return response()->json($restaurant, 200);
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

        $validatedData = $request->validate($this->getParamsToValidate());

        $restaurant->update($validatedData);

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurant_tags()->detach();
        $restaurant->restaurant_tags()->attach($restaurantTags);

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurant_categories()->detach();
        $restaurant->restaurant_categories()->attach($restaurantCategories);

        return response()->json($restaurant->load(['restaurant_categories', 'restaurant_tags']), 200);
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
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required',
            'name_mm' => 'required',
            'is_official' => 'required|boolean',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'restaurant_categories' => 'required|array',
            'restaurant_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
            'restaurant_branch' => 'required',
            'restaurant_branch.slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'restaurant_branch.name' => 'required|string',
            'restaurant_branch.name_mm' => 'required|string',
            'restaurant_branch.address' => 'required',
            'restaurant_branch.contact_number' => 'required',
            'restaurant_branch.opening_time' => 'required|date_format:H:i',
            'restaurant_branch.closing_time' => 'required|date_format:H:i',
            'restaurant_branch.latitude' => 'nullable|numeric',
            'restaurant_branch.longitude' => 'nullable|numeric',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:restaurants';
        }

        return $params;
    }

    private function createRestaurantBranch($restaurantId, $restaurantBranch)
    {
        $restaurantBranch['restaurant_branch_id'] = $this->getRestaurantBranchId($restaurantBranch['restaurant_branch_slug']);
        $restaurantBranch['restaurant_id'] = $restaurantId;
        RestaurantBranch::create($restaurantBranch);
    }

    private function getRestaurantBranchId($slug)
    {
        return RestaurantBranch::where('slug', $slug)->first()->id;
    }


    /**
    * Toggle the is_enable column for restaurant table.
    *
    * @param  int  $slug
    * @return \Illuminate\Http\Response
    */
    public function toggleEnable($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $restaurant->is_enable = !$restaurant->is_enable;
        $restaurant->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    /**
    * Toggle the is_official column for restaurant table.
    *
    * @param  int  $slug
    * @return \Illuminate\Http\Response
    */
    public function toggleOfficial($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $restaurant->is_official = !$restaurant->is_official;
        $restaurant->save();
        return response()->json(['message' => 'Success.'], 200);
    }
}
