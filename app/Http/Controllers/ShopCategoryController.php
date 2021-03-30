<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopCategoryController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/shop-categories",
     *      operationId="getShopCategoryLists",
     *      tags={"Shop Category"},
     *      summary="Get list of shop categories",
     *      description="Returns list of shop categories",
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
        return ShopCategory::with('shopSubCategories')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/shop-categories",
     *      operationId="storeShopCategory",
     *      tags={"Shop Category"},
     *      summary="Create a Shop Category",
     *      description="Returns newly created shop category",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created shop category object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ShopCategory")
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

        $shopCategory = ShopCategory::create($request->validate(
            [
                'name' => 'required|unique:shop_categories',
                'slug' => 'required|unique:shop_categories',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ]
        ));

        $this->updateFile($request->image_slug, 'shop_categories', $shopCategory->slug);

        return response()->json($shopCategory, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/shop-categories/{slug}",
     *      operationId="showShopCategory",
     *      tags={"Shop Category"},
     *      summary="Get One Shop Category",
     *      description="Returns a requested shop category",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested shop category",
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
        $shopCategory = ShopCategory::with('shopSubCategories')->where('slug', $slug)->firstOrFail();
        return response()->json($shopCategory, 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/shop-categories/{slug}",
     *      operationId="updateShopCategory",
     *      tags={"Shop Category"},
     *      summary="Update a Shop Category",
     *      description="Update a requested shop category",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a shop category",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New shop category data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ShopCategory")
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
        $shopCategory = ShopCategory::where('slug', $slug)->firstOrFail();

        $shopCategory->update($request->validate([
            'name' => [
                'required',
                Rule::unique('shop_categories')->ignore($shopCategory->id),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'shop_categories', $shopCategory->slug);
        }

        return response()->json($shopCategory, 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/shop-categories/{slug}",
     *      operationId="deleteShopCategory",
     *      tags={"Shop Category"},
     *      summary="Delete One Shop Category",
     *      description="Delete one specific shop category",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested shop category",
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
        $shopCategory = ShopCategory::where('slug', $slug)->firstOrFail();

        foreach ($shopCategory->images as $image) {
            $this->deleteFile($image->slug);
        }

        $shopCategory->delete();

        return response()->json(['message' => 'successfully deleted'], 200);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/shops/{slug}/shop-categories",
     *      operationId="getshopCategoryListsByShop",
     *      tags={"Shop Category"},
     *      summary="Get Shop Categories By Shop",
     *      description="Returns list of shop categories",
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
    public function getCategoriesByShop(Request $request, $slug)
    {
        return ShopCategory::whereHas('shops', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'shop_categories' => 'nullable|array',
            'shop_categories.*.name' => 'required|unique:shop_categories',
        ]);

        foreach ($validatedData['shop_categories'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            ShopCategory::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
