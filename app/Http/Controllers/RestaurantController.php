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

    public function index(Request $request)
    {
        return Restaurant::with('restaurantCategories', 'restaurantTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate([
            'slug' => 'required|unique:restaurants',
            'name' => 'required|unique:restaurants',
            'name_mm' => 'unique:restaurants',
            'is_official' => 'required|boolean',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'restaurant_categories' => 'required|array',
            'restaurant_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
            'restaurant_branch' => 'required',
            'restaurant_branch.name' => 'required|string',
            'restaurant_branch.name_mm' => 'required|string',
            'restaurant_branch.address' => 'required',
            'restaurant_branch.contact_number' => 'required',
            'restaurant_branch.opening_time' => 'required|date_format:H:i',
            'restaurant_branch.closing_time' => 'required|date_format:H:i',
            'restaurant_branch.latitude' => 'nullable|numeric',
            'restaurant_branch.longitude' => 'nullable|numeric',
            'restaurant_branch.township_id' => 'required|numeric',
        ]);

        $restaurant = Restaurant::create($validatedData);
        $restaurantId = $restaurant->id;

        $this->createRestaurantBranch($restaurantId, $validatedData['restaurant_branch']);

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurantTags()->attach($restaurantTags);

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurantCategories()->attach($restaurantCategories);

        return response()->json($restaurant->refresh()->load('restaurantTags', 'restaurantCategories', 'restaurantBranches'), 201);
    }

    public function show($slug)
    {
        $restaurant = Restaurant::with('restaurantCategories', 'restaurantTags')->where('slug', $slug)->firstOrFail();
        return response()->json($restaurant, 200);
    }

    public function update(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' =>  [
                'required',
                Rule::unique('restaurants')->ignore($restaurant->id),
            ],
            'name_mm' => [
                'required',
                Rule::unique('restaurants')->ignore($restaurant->id)
            ],
            'is_official' => 'required|boolean',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'restaurant_categories' => 'required|array',
            'restaurant_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);

        $restaurant->update($validatedData);

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurantTags()->detach();
        $restaurant->restaurantTags()->attach($restaurantTags);

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurantCategories()->detach();
        $restaurant->restaurantCategories()->attach($restaurantCategories);

        return response()->json($restaurant->load(['restaurantCategories', 'restaurantTags']), 200);
    }

    public function destroy($slug)
    {
        Restaurant::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $restaurant->is_enable = !$restaurant->is_enable;
        $restaurant->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    public function toggleOfficial($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $restaurant->is_official = !$restaurant->is_official;
        $restaurant->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    private function createRestaurantBranch($restaurantId, $restaurantBranch)
    {
        $restaurantBranch['slug'] = $this->generateUniqueSlug();
        $restaurantBranch['restaurant_id'] = $restaurantId;
        RestaurantBranch::create($restaurantBranch);
    }

    public function addRestaurantCategories(Request $request, $slug)
    {
        $restaurant = $request->validate([
            'restaurant_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);

        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurantCategories()->detach();
        $restaurant->restaurantCategories()->attach($restaurantCategories);

        return response()->json($restaurant->load(['restaurantCategories', 'restaurantTags']), 201);
    }

    public function removeRestaurantCategories(Request $request, $slug)
    {
        $restaurant = $request->validate([
            'restaurant_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->restaurant_categories)->pluck('id');
        $restaurant->restaurantCategories()->detach($restaurantCategories);

        return response()->json($restaurant->load(['restaurantCategories', 'restaurantTags']), 201);
    }
}
