<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantTag;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class RestaurantController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('restaurants', 'id', $request->by ? $request->by : 'desc', $request->order);

        return Restaurant::with('availableTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate(
            [
                'slug' => 'required|unique:restaurants',
                'name' => 'required|unique:restaurants',
                'is_enable' => 'required|boolean',
                'restaurant_tags' => 'nullable|array',
                'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
                'restaurant_branch' => 'required',
                'restaurant_branch.name' => 'required|string',
                'restaurant_branch.address' => 'required',
                'restaurant_branch.contact_number' => 'required|phone:MM',
                'restaurant_branch.opening_time' => 'required|date_format:H:i',
                'restaurant_branch.closing_time' => 'required|date_format:H:i',
                'restaurant_branch.latitude' => 'required|numeric',
                'restaurant_branch.longitude' => 'required|numeric',
                'restaurant_branch.township_slug' => 'required|exists:App\Models\Township,slug',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
                'cover_slugs' => 'nullable|array',
                'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'restaurant_branch.contact_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['restaurant_branch']['contact_number'] = PhoneNumber::make($validatedData['restaurant_branch']['contact_number'], 'MM');
        $restaurant = Restaurant::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'restaurants', $restaurant->slug);
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'restaurants', $restaurant->slug);
            }
        }

        $this->createRestaurantBranch($restaurant->id, $validatedData['restaurant_branch']);

        if ($request->restaurant_tags) {
            $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
            $restaurant->availableTags()->attach($restaurantTags);
        }

        return response()->json($restaurant->load('availableTags', 'availableCategories', 'restaurantBranches'), 201);
    }

    public function show(Restaurant $restaurant)
    {
        return response()->json($restaurant->load('availableTags'), 200);
    }

    public function update(Request $request, Restaurant $restaurant)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('restaurants')->ignore($restaurant->id),
            ],
            'is_enable' => 'required|boolean',
            'restaurant_tags' => 'nullable|array',
            'restaurant_tags.*' => 'exists:App\Models\RestaurantTag,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',
        ]);

        $restaurant->update($validatedData);

        $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
        $restaurant->availableTags()->detach();
        $restaurant->availableTags()->attach($restaurantTags);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'restaurants', $restaurant->slug);
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'restaurants', $restaurant->slug);
            }
        }

        return response()->json($restaurant->load(['availableCategories', 'availableTags']), 200);
    }

    public function destroy(Restaurant $restaurant)
    {
        foreach ($restaurant->images as $image) {
            $this->deleteFile($image->slug);
        }

        $restaurant->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable(Restaurant $restaurant)
    {
        $restaurant->update(['is_enable' => !$restaurant->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleStatusUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Restaurant,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
            $restaurant->update(['is_enable' => $request->is_enable]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    private function createRestaurantBranch($restaurantId, $restaurantBranch)
    {
        $restaurantBranch['slug'] = $this->generateUniqueSlug();
        $restaurantBranch['restaurant_id'] = $restaurantId;
        $restaurantBranch['township_id'] = $this->getTownshipIdBySlug($restaurantBranch['township_slug']);
        RestaurantBranch::create($restaurantBranch);
    }

    private function getTownshipIdBySlug($slug)
    {
        return Township::where('slug', $slug)->first()->id;
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate(
            [
                'restaurants' => 'nullable|array',
                'restaurants.*.name' => 'required|unique:restaurants',
                'restaurants.*.is_enable' => 'required|boolean',
                'restaurants.*.restaurant_branch' => 'required',
                'restaurants.*.restaurant_branch.name' => 'required|string',
                'restaurants.*.restaurant_branch.address' => 'required',
                'restaurants.*.restaurant_branch.contact_number' => 'required|phone:MM',
                'restaurants.*.restaurant_branch.opening_time' => 'required|date_format:H:i',
                'restaurants.*.restaurant_branch.closing_time' => 'required|date_format:H:i',
                'restaurants.*.restaurant_branch.latitude' => 'required|numeric',
                'restaurants.*.restaurant_branch.longitude' => 'required|numeric',
                'restaurants.*.restaurant_branch.township_slug' => 'required|exists:App\Models\Township,slug',
            ],
            [
                'restaurants.*.restaurant_branch.contact_number.phone' => 'Invalid phone number.',
            ]
        );

        foreach ($validatedData['restaurants'] as $data) {
            $data['restaurant_branch']['contact_number'] = PhoneNumber::make($data['restaurant_branch']['contact_number'], 'MM');
            $data['slug'] = $this->generateUniqueSlug();
            $restaurant = Restaurant::create($data);
            $restaurantId = $restaurant->id;
            $this->createRestaurantBranch($restaurantId, $data['restaurant_branch']);
        }

        return response()->json($validatedData, 200);
    }
}
