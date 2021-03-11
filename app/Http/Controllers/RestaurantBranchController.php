<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\RestaurantBranch;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use App\Models\Township;

class RestaurantBranchController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return RestaurantBranch::with('restaurant', 'township')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('contact_number', $request->filter)
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

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

    public function show($slug)
    {
        $restaurantBranch = RestaurantBranch::with('restaurant', 'township')->where('slug', $slug)->firstOrFail();
        return response()->json($restaurantBranch, 200);
    }

    public function update(Request $request, $slug)
    {
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
            ],
            'name_mm' => [
                'nullable',
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

    public function getBranchesByRestaurant(Request $request, $slug)
    {
        return RestaurantBranch::whereHas('restaurant', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where('name', 'LIKE', '%' . $request->filter . '%')
            ->paginate(10);
    }

    public function getBranchesByTownship(Request $request, $slug)
    {
        return RestaurantBranch::whereHas('township', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function toggleEnable($slug)
    {
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();
        $restaurantBranch->is_enable = !$restaurantBranch->is_enable;
        $restaurantBranch->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    public function addAvailableMenus(Request $request, $slug)
    {
        $restaurantBranch = $request->validate([
            'available_menus.*' => 'exists:App\Models\Menu,slug',
        ]);

        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $availableMenus = Menu::whereIn('slug', $request->available_menus)->pluck('id');
        $restaurantBranch->availableMenus()->detach();
        $restaurantBranch->availableMenus()->attach($availableMenus);

        return response()->json($restaurantBranch->load(['availableMenus', 'restaurant', 'township']), 201);
    }

    public function removeAvailableMenus(Request $request, $slug)
    {
        $restaurantBranch = $request->validate([
            'available_menus.*' => 'exists:App\Models\Menu,slug',
        ]);
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $availableMenus = Menu::whereIn('slug', $request->available_menus)->pluck('id');
        $restaurantBranch->availableMenus()->detach($availableMenus);

        return response()->json($restaurantBranch->load(['availableMenus', 'restaurant', 'township']), 201);
    }

    public function updateWithTagsAndCategories(Request $request, $slug)
    {
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
            ],
            'name_mm' => [
                'nullable',
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
            'is_enable' => 'nullable|boolean',
            'restaurant_tags' => 'required|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'available_categories' => 'nullable|array',
            'available_categories.*' => 'exists:App\Models\RestaurantCategory,slug',
        ]);

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch->update($validatedData);
        $restaurant = Restaurant::where('slug', $request->restaurant_slug)->firstOrFail();

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->availableTags()->detach();
        $restaurant->availableTags()->attach($restaurantTags);

        if ($request->available_categories) {
            $restaurantCategories = RestaurantCategory::whereIn('slug', $request->available_categories)->pluck('id');
            $restaurant->availableCategories()->detach();
            $restaurant->availableCategories()->attach($restaurantCategories);
        }
        return response()->json($restaurantBranch->load('restaurant', 'township'), 200);
    }
}
