<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use App\Models\RestaurantTag;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class RestaurantBranchController extends Controller
{
    use StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-branches",
     *      operationId="getRestaurantBranchLists",
     *      tags={"Restaurant Branches"},
     *      summary="Get list of restaurant branches",
     *      description="Returns list of restaurant branches",
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
        return RestaurantBranch::with('restaurant', 'township')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('contact_number', $request->filter)
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/restaurant-branches",
     *      operationId="storeRestaurantBranch",
     *      tags={"Restaurant Branches"},
     *      summary="Create a restaurant branch",
     *      description="Returns newly created restaurant branch",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created restaurant branch object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantBranch")
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

        $validatedData = $request->validate([
            'slug' => 'required|unique:restaurant_branches',
            'name' => 'required',
            'address' => 'nullable',
            'contact_number' => 'required|phone:MM',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
            'is_enable' => 'required|boolean',
            [
                'contact_number.phone' => 'Invalid phone number.',
            ]
        ]);

        $validatedData['contact_number'] = PhoneNumber::make($validatedData['contact_number'], 'MM');

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch = RestaurantBranch::create($validatedData);
        return response()->json($restaurantBranch->load('restaurant', 'township'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-branches/{slug}",
     *      operationId="showRestaurantBranch",
     *      tags={"Restaurant Branches"},
     *      summary="Get One Restaurant Branch",
     *      description="Returns a requested restaurant branch",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant branch",
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
        $restaurantBranch = RestaurantBranch::with('restaurant', 'township')->where('slug', $slug)->firstOrFail();
        return response()->json($restaurantBranch, 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/restaurant-branches/{slug}",
     *      operationId="updateRestaurantBranch",
     *      tags={"Restaurant Branches"},
     *      summary="Update a restaurant branch",
     *      description="Update a requested restaurant branch",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a restaurant branch",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New restaurant branch data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantBranch")
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
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_branches')->ignore($restaurantBranch->id),
            ],
            'address' => 'nullable',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
            'is_enable' => 'required|boolean',
        ]);

        $validatedData['restaurant_id'] = $this->getRestaurantId($request->restaurant_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $restaurantBranch->update($validatedData);
        return response()->json($restaurantBranch->load('restaurant', 'township'), 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/restaurant-branches/{slug}",
     *      operationId="deleteRestaurantBranch",
     *      tags={"Restaurants"},
     *      summary="Delete One Restaurant Branch",
     *      description="Delete one specific restaurant branch",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant branch",
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
     * @OA\Get(
     *      path="/api/v2/admin/restaurants/{slug}/restaurant-branches",
     *      operationId="getBranchesByRestaurant",
     *      tags={"Restaurant Branches"},
     *      summary="Get Branches By Restaurant",
     *      description="Returns requested list of restaurant branches",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the Restaurant",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *       @OA\Parameter(
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
    public function getBranchesByRestaurant(Request $request, $slug)
    {
        return RestaurantBranch::whereHas('restaurant', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where('name', 'LIKE', '%' . $request->filter . '%')
            ->paginate(10);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/townships/{slug}/restaurant-branches",
     *      operationId="getBranchesByTownship",
     *      tags={"Restaurant Branches"},
     *      summary="Get Branches By Restaurant",
     *      description="Returns requested list of restaurant branches",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the Township",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
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
    public function getBranchesByTownship(Request $request, $slug)
    {
        return RestaurantBranch::whereHas('township', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/restaurant-branches/toggle-enable/{slug}",
     *      operationId="enableRestaurantBranch",
     *      tags={"Restaurant Branches"},
     *      summary="Enable Restaurant Branch",
     *      description="Enable a restaurant branch",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the restaurant branch",
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
        $restaurantBranch = RestaurantBranch::where('slug', $slug)->firstOrFail();
        $restaurantBranch->is_enable = !$restaurantBranch->is_enable;
        $restaurantBranch->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/restaurant-branches/add-available-menus/{slug}",
     *      operationId="addAvailableMenus",
     *      tags={"Restaurant Branches"},
     *      summary="Add available menus",
     *      description="Returns newly added available menus",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the menu",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Added available menus",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantBranch")
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

    /**
     * @OA\Post(
     *      path="/api/v2/admin/restaurant-branches/remove-available-menus/{slug}",
     *      operationId="removeAvailableMenus",
     *      tags={"Restaurant Branches"},
     *      summary="Remvoe available menus",
     *      description="Returns newly removed available menus",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the menu",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Removed available menus",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantBranch")
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
            'address' => 'nullable',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
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

    public function toggleAvailable(Request $request, $restaurantBranchSlug, $slug)
    {
        $validatedData = $request->validate([
            'is_available' => 'required|boolean',
        ]);

        $restaurantBranch = RestaurantBranch::with('availableMenus')
            ->where('slug', $restaurantBranchSlug)
            ->firstOrFail();

        $availableMenus = Menu::where('slug', $slug)->firstOrFail();
        $restaurantBranch->availableMenus()->sync([$availableMenus->id => ['is_available' => $validatedData['is_available']]], false);
        // $restaurantBranch->availableMenus()->save($availableMenus, ['is_available' => $validatedData['is_available']]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'restaurant_branches' => 'nullable|array',
            'restaurant_branches.*.name' => 'required',
            'restaurant_branches.*.is_enable' => 'required|boolean',
            'restaurant_branches.*.address' => 'nullable',
            'restaurant_branches.*.contact_number' => 'required',
            'restaurant_branches.*.opening_time' => 'required|date_format:H:i',
            'restaurant_branches.*.closing_time' => 'required|date_format:H:i',
            'restaurant_branches.*.latitude' => 'required|numeric',
            'restaurant_branches.*.longitude' => 'required|numeric',
            'restaurant_branches.*.restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'restaurant_branches.*.township_slug' => 'nullable|exists:App\Models\Township,slug',
        ]);

        foreach ($validatedData['restaurant_branches'] as $data) {
            $data['restaurant_id'] = $this->getRestaurantId($data['restaurant_slug']);
            $data['township_id'] = $this->getTownshipId($data['township_slug']);

            $data['slug'] = $this->generateUniqueSlug();
            RestaurantBranch::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
