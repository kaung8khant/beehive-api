<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Helpers\FileHelper;
use App\Models\RestaurantCategory;

class RestaurantCategoryController extends Controller
{
    use StringHelper, FileHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-categories",
     *      operationId="getRestaurantCategoryLists",
     *      tags={"Restaurant Categories"},
     *      summary="Get list of restaurant categories",
     *      description="Returns list of restaurant categories",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
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
    public function index(Request $request)
    {
        return RestaurantCategory::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *      path="/api/v2/admin/restaurant-categories",
     *      operationId="storeRestaurantCategory",
     *      tags={"Restaurant Categories"},
     *      summary="Create a restaurant category",
     *      description="Returns newly created restaurant category",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created restaurant category object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantCategory")
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

        $restaurantCategory = RestaurantCategory::create($request->validate([
            'name' => 'required|unique:restaurant_categories',
            'slug' => 'required|unique:restaurant_categories',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        $this->updateFile($request->image_slug, 'restaurant_categories', $restaurantCategory->slug);
        return response()->json($restaurantCategory, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-categories/{slug}",
     *      operationId="showRestaurantCategory",
     *      tags={"Restaurant Categories"},
     *      summary="Get One Restaurant Category",
     *      description="Returns a requested restaurant category",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a restaurant category",
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
        $restaurantCategory = RestaurantCategory::where('slug', $slug)->firstOrFail();
        return response()->json($restaurantCategory, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *      path="/api/v2/admin/restaurant-categories/{slug}",
     *      operationId="updateRestaurantCategory",
     *      tags={"Restaurant Categories"},
     *      summary="Update a Restaurant Category",
     *      description="Update a requested restaurant category",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a restaurant category",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New restaurant category data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantCategory")
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
        $restaurantCategory = RestaurantCategory::where('slug', $slug)->firstOrFail();

        $restaurantCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_categories')->ignore($restaurantCategory->id),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'restaurant_categories', $restaurantCategory->slug);
        }
        return response()->json($restaurantCategory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     *      path="/api/v2/admin/restaurant-categories/{slug}",
     *      operationId="deleteRestaurantCategory",
     *      tags={"Restaurant Categories"},
     *      summary="Delete One restaurant category",
     *      description="Delete one specific restaurant category",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant category",
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
        $restaurantCategory = RestaurantCategory::where('slug', $slug)->firstOrFail();

        foreach ($restaurantCategory->images as $image) {
            $this->deleteFile($image->slug);
        }

        $restaurantCategory->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    /**
     * Display a listing of the restaurant categories by one restaurant.
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurants/{slug}/restaurant-categories",
     *      operationId="getCategoriesByRestaurant",
     *      tags={"Restaurant Categories"},
     *      summary="Get Categories By Restaurant",
     *      description="Returns requested list of restaurant categories",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the restaurant",
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
    public function getCategoriesByRestaurant(Request $request, $slug)
    {
        return RestaurantCategory::whereHas('restaurants', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData=$request->validate([
            'restaurant_categories' => 'nullable|array',
            'restaurant_categories.*.name' => 'required|unique:restaurant_categories',
        ]);

        foreach ($validatedData['restaurant_categories'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            RestaurantCategory::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
