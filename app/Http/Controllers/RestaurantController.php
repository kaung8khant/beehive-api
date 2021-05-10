<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class RestaurantController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurants",
     *      operationId="getRestaurantLists",
     *      tags={"Restaurants"},
     *      summary="Get list of restaurants",
     *      description="Returns list of restaurants",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *        name="filter",
     *        description="Filter",
     *        required=false,
     *        in="query",
     *        @OA\Schema(
     *            type="string"
     *        ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index(Request $request)
    {
        return Restaurant::with('availableTags')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/restaurants",
     *      operationId="storeRestaurant",
     *      tags={"Restaurants"},
     *      summary="Create a restaurant",
     *      description="Returns newly created restaurant",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created restaurant object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Restaurant")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
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
        // $townshipId = $this->getTownshipIdBySlug($request->restaurant_branch['township_slug']);

        $restaurant = Restaurant::create($validatedData);

        $restaurantId = $restaurant->id;

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'restaurants', $restaurant->slug);
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'restaurants', $restaurant->slug);
            }
        }

        $this->createRestaurantBranch($restaurantId, $validatedData['restaurant_branch']);

        if ($request->restaurant_tags) {
            $restaurantTags = RestaurantTag::whereIn('slug', $request->restaurant_tags)->pluck('id');
            $restaurant->availableTags()->attach($restaurantTags);
        }

        return response()->json($restaurant->load('availableTags', 'availableCategories', 'restaurantBranches'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurants/{slug}",
     *      operationId="showRestaurant",
     *      tags={"Restaurants"},
     *      summary="Get One Restaurant",
     *      description="Returns a requested restaurant",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show($slug)
    {
        $restaurant = Restaurant::with('availableTags')->where('slug', $slug)->firstOrFail();
        return response()->json($restaurant, 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/restaurants/{slug}",
     *      operationId="updateRestaurant",
     *      tags={"Restaurants"},
     *      summary="Update a restaurant",
     *      description="Update a requested restaurant",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a restaurant",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New restaurant data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Restaurant")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function update(Request $request, $slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

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

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/restaurants/{slug}",
     *      operationId="deleteRestaurant",
     *      tags={"Restaurants"},
     *      summary="Delete One Restaurant",
     *      description="Delete one specific restaurant",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function destroy($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();

        foreach ($restaurant->images as $image) {
            $this->deleteFile($image->slug);
        }

        $restaurant->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/restaurants/toggle-enable/{slug}",
     *      operationId="enableRestaurant",
     *      tags={"Restaurants"},
     *      summary="Enable Restaurant",
     *      description="Enable a restaurant",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the Restaurant",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function toggleEnable($slug)
    {
        $restaurant = Restaurant::where('slug', $slug)->firstOrFail();
        $restaurant->is_enable = !$restaurant->is_enable;
        $restaurant->save();
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
            $restaurant->is_enable = $request->is_enable;
            $restaurant->save();
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    // public function multipleStatusUpdate(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'restaurants' => 'required|array',
    //         'restaurants.*.slug' => 'required|exists:App\Models\Restaurant,slug',
    //         'restaurants.*.is_enable' => 'required|boolean',
    //     ]);

    //     foreach ($validatedData['restaurants'] as $data) {

    //         $restaurant = Restaurant::where('slug', $data['slug'])->firstOrFail();
    //         $restaurant->is_enable = $data['is_enable'];
    //         $restaurant->save();
    //     }

    //     return response()->json($validatedData, 200);
    // }

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
