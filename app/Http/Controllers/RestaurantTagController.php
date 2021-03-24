<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\RestaurantTag;

class RestaurantTagController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-tags",
     *      operationId="getRestaurantTags",
     *      tags={"Restaurant Tags"},
     *      summary="Get list of restaurant tags",
     *      description="Returns list of restaurant tags",
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
        return RestaurantTag::where('name', 'LIKE', '%' . $request->filter . '%')
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
     *      path="/api/v2/admin/restaurant-tags",
     *      operationId="storeRestaurantTag",
     *      tags={"Restaurant Tags"},
     *      summary="Create a restaurant tag",
     *      description="Returns newly created restaurant tag",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created restaurant tag object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantTag")
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

        $tag = RestaurantTag::create($request->validate(
            [
                'name' => 'required|unique:restaurant_tags',
                'slug' => 'required|unique:restaurant_tags',
            ]
        ));
        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestaurantTag  $tag
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurant-tags/{slug}",
     *      operationId="showRestaurantTag",
     *      tags={"Restaurant Tags"},
     *      summary="Get One RestaurantTag",
     *      description="Returns a requested Restaurant Tag",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant tag",
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
        return response()->json(RestaurantTag::where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RestaurantTag  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *      path="/api/v2/admin/restaurant-tags/{slug}",
     *      operationId="updateRestaurantTag",
     *      tags={"Restaurant Tags"},
     *      summary="Update a restaurant tag",
     *      description="Update a restaurant tag",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a restaurant tag",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New restaurant tag data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/RestaurantTag")
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
        $tag = RestaurantTag::where('slug', $slug)->firstOrFail();

        $tag->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_tags')->ignore($tag->id),
            ]
        ]));

        return response()->json($tag, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestaurantTag  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     *      path="/api/v2/admin/restaurant-tags/{slug}",
     *      operationId="deleteRestaurantTags",
     *      tags={"Restaurant Tags"},
     *      summary="Delete One Restaurant Tag",
     *      description="Delete one restaurant tag",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested restaurant tag",
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
        RestaurantTag::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    /**
     * Display a listing of the restaurant tags by one restaurant.
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/restaurants/{slug}/restaurant-tags",
     *      operationId="getTagsByRestaurant",
     *      tags={"Restaurant Tags"},
     *      summary="Get Tags By Restaurant",
     *      description="Returns requested list of restaruant tags",
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
    public function getTagsByRestaurant(Request $request, $slug)
    {
        return RestaurantTag::whereHas('restaurants', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData=$request->validate([
            'restaurant_tags' => 'nullable|array',
            'restaurant_tags.*.name' => 'required|unique:restaurant_tags',
        ]);

        $restaurantTags=array();
        foreach ($validatedData['restaurant_tags'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            array_push($restaurantTags, RestaurantTag::create($data));
        }

        return response()->json($restaurantTags, 201);
    }
}
