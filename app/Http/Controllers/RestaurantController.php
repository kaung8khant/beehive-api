<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use App\Models\Township;

class RestaurantController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return Restaurant::with('availableCategories', 'restaurantTags')
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
            'is_enable' => 'required|boolean',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'available_categories' => 'nullable|array',
            'available_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
            'restaurant_branch' => 'required',
            'restaurant_branch.name' => 'required|string',
            'restaurant_branch.name_mm' => 'required|string',
            'restaurant_branch.address' => 'required',
            'restaurant_branch.contact_number' => 'required',
            'restaurant_branch.opening_time' => 'required|date_format:H:i',
            'restaurant_branch.closing_time' => 'required|date_format:H:i',
            'restaurant_branch.latitude' => 'nullable|numeric',
            'restaurant_branch.longitude' => 'nullable|numeric',
            'restaurant_branch.township_slug' => 'required|exists:App\Models\Township,slug',
        ]);
        $townshipId = $this->getTownshipIdBySlug($request->restaurant_branch['township_slug']);

        $restaurant = Restaurant::create($validatedData);
        $restaurantId = $restaurant->id;

        $this->createRestaurantBranch($restaurantId, $townshipId, $validatedData['restaurant_branch']);

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurantTags()->attach($restaurantTags);

        if ($request->available_categories) {
            $restaurantCategories = RestaurantCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $restaurant->availableCategories()->attach($restaurantCategories);
        }
        return response()->json($restaurant->load('restaurantTags', 'availableCategories', 'restaurantBranches'), 201);
    }

    public function show($slug)
    {
        $restaurant = Restaurant::with('availableCategories', 'restaurantTags')->where('slug', $slug)->firstOrFail();
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
            'is_enable' => 'required|boolean',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'available_categories' => 'nullable|array',
            'available_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);

        $restaurant->update($validatedData);

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->restaurantTags()->detach();
        $restaurant->restaurantTags()->attach($restaurantTags);

        if ($request->available_categories) {
            $restaurantCategories = RestaurantCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $restaurant->availableCategories()->detach();
            $restaurant->availableCategories()->attach($restaurantCategories);
        }
        return response()->json($restaurant->load(['availableCategories', 'restaurantTags']), 200);
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

    private function createRestaurantBranch($restaurantId, $townshipId, $restaurantBranch)
    {
        $restaurantBranch['slug'] = $this->generateUniqueSlug();
        $restaurantBranch['restaurant_id'] = $restaurantId;
        $restaurantBranch['township_id'] = $townshipId;
        RestaurantBranch::create($restaurantBranch);
    }

    private function getTownshipIdBySlug($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    public function addRestaurantCategories(Request $request, $slug)
    {
        $restaurant = $request->validate([
            'available_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);

        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->available_categories)->pluck('id');
        $restaurant->availableCategories()->detach();
        $restaurant->availableCategories()->attach($restaurantCategories);

        return response()->json($restaurant->load(['availableCategories', 'restaurantTags']), 201);
    }

    public function removeRestaurantCategories(Request $request, $slug)
    {
        $restaurant = $request->validate([
            'available_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        $restaurantCategories = RestaurantCategory::whereIn('slug', $request->available_categories)->pluck('id');
        $restaurant->availableCategories()->detach($restaurantCategories);

        return response()->json($restaurant->load(['availableCategories', 'restaurantTags']), 201);
    }
}
