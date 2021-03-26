<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\ShopTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopTagController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/shop-tags",
     *      operationId="getShopTagLists",
     *      tags={"ShopTags"},
     *      summary="Get list of shop tags",
     *      description="Returns list of shop tags",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
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
        return ShopTag::where('name', 'LIKE', '%' . $request->filter . '%')
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
     *      path="/api/v2/admin/shop-tags",
     *      operationId="storeShopTag",
     *      tags={"ShopTags"},
     *      summary="Create a Shop Tag",
     *      description="Returns newly created shop tag",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created shop tag object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ShopTag")
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

        $tag = ShopTag::create($request->validate(
            [
                'name' => 'required|unique:shop_tags',
                'slug' => 'required|unique:shop_tags',
            ]
        ));
        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ShopTag  $tag
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/shop-tags/{slug}",
     *      operationId="showShopTag",
     *      tags={"ShopTags"},
     *      summary="Get One Shop Tag",
     *      description="Returns a requested shop tag",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested shop tag",
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
        return response()->json(ShopTag::where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShopTag  $tag
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *      path="/api/v2/admin/shop-tags/{slug}",
     *      operationId="updateShopTag",
     *      tags={"ShopTags"},
     *      summary="Update a shop tag",
     *      description="Update a requested shop tag",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a shop tag",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New shop tag data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ShopTag")
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
        $tag = ShopTag::where('slug', $slug)->firstOrFail();

        $tag->update($request->validate([
            'name' => [
                'required',
                Rule::unique('shop_tags')->ignore($tag->id),
            ],

        ]));

        return response()->json($tag, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ShopTag  $tag
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/shop-tags/{slug}",
     *      operationId="deleteShopTag",
     *      tags={"ShopTags"},
     *      summary="Delete One Shop Tag",
     *      description="Delete one specific shop tag",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested Shop Tag",
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
        ShopTag::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    /**
     * Display a listing of the shop tags by one shop.
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/shops/{slug}/shop-tags",
     *      operationId="getShopTagsByShop",
     *      tags={"ShopTags"},
     *      summary="Get Shop tags By Shop",
     *      description="Returns list of shop tags",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested shop",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
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
    public function getTagsByShop(Request $request, $slug)
    {
        return ShopTag::whereHas('shops', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'shop_tags' => 'nullable|array',
            'shop_tags.*.name' => 'required|unique:shop_tags',
        ]);

        foreach ($validatedData['shop_tags'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            ShopTag::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
