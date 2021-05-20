<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Models\RestaurantTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestaurantTagController extends Controller
{
    use StringHelper;

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
        $sorting = CollectionHelper::getSorting('restaurant_tags', 'name', $request->by, $request->order);

        return RestaurantTag::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

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
    public function show(RestaurantTag $restaurantTag)
    {
        return response()->json($restaurantTag, 200);
    }

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
    public function update(Request $request, RestaurantTag $restaurantTag)
    {
        $restaurantTag->update($request->validate([
            'name' => [
                'required',
                Rule::unique('restaurant_tags')->ignore($restaurantTag->id),
            ],
        ]));

        return response()->json($restaurantTag, 200);
    }

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
    public function destroy(RestaurantTag $restaurantTag)
    {
        $restaurantTag->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

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
        $sorting = CollectionHelper::getSorting('restaurant_tags', 'name', $request->by, $request->order);

        return RestaurantTag::whereHas('restaurants', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'restaurant_tags' => 'nullable|array',
            'restaurant_tags.*.name' => 'required|unique:restaurant_tags',
        ]);

        foreach ($validatedData['restaurant_tags'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            RestaurantTag::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
