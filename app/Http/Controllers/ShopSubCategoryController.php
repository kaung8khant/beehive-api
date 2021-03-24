<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;

class ShopSubCategoryController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Get(
     *      path="/api/v2/admin/sub-categories",
     *      operationId="getSubcategoryLists",
     *      tags={"Subcategories"},
     *      summary="Get list of subcategories",
     *      description="Returns list of subcategories",
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
        return ShopSubCategory::with('shopCategory')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
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
     *      path="/api/v2/admin/sub-categories",
     *      operationId="storeSubcategory",
     *      tags={"Subcategories"},
     *      summary="Create a Subcategory",
     *      description="Returns newly created subcategory",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created subcategory object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ShopSubCategory")
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
            'name' => 'required|unique:shop_sub_categories',
            'slug' => 'required|unique:shop_sub_categories',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);

        $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);

        $subCategory = ShopSubCategory::create($validatedData);
        return response()->json($subCategory->load('shopCategory'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Get(
     *      path="/api/v2/admin/sub-categories/{slug}",
     *      operationId="showSubcategory",
     *      tags={"Subcategories"},
     *      summary="Get One Subcategory",
     *      description="Returns a requested subcategory",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested subcategory",
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
        $subCategory = ShopSubCategory::with('shopCategory')->where('slug', $slug)->firstOrFail();
        return response()->json($subCategory, 200);
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
     *      path="/api/v2/admin/sub-categories/{slug}",
     *      operationId="updateSubCategory",
     *      tags={"Subcategories"},
     *      summary="Update a Subcategory",
     *      description="Update a requested subcategory",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a subcategory",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New subcategory data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ShopSubCategory")
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
        $subCategory = ShopSubCategory::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('shop_sub_categories')->ignore($subCategory->id),
            ],
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);

        $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);
        $subCategory->update($validatedData);

        // Update the category ids of related products
        foreach ($subCategory->products as $product) {
            $product->update([
                'shop_category_id' => $validatedData['shop_category_id']
            ]);
        }

        return response()->json($subCategory->load('shopCategory')->unsetRelation('products'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/sub-categories/{slug}",
     *      operationId="deleteSubcategory",
     *      tags={"Subcategories"},
     *      summary="Delete One Subcategory",
     *      description="Delete one specific subcategory",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested subcategory",
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
        ShopSubCategory::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getShopCategoryId($slug)
    {
        return ShopCategory::where('slug', $slug)->first()->id;
    }

    /**
     * Display a listing of the sub categories by one shop category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/shop-categories/{slug}/sub-categories",
     *      operationId="getSubCategoriesByCategory",
     *      tags={"Subcategories"},
     *      summary="Get Subcategories By Shop Category",
     *      description="Returns list of subcategories",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested shop category",
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
    public function getSubCategoriesByCategory(Request $request, $slug)
    {
        return ShopSubCategory::whereHas('shopCategory', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData=$request->validate([
            'shop_sub_categories' => 'nullable|array',
            'shop_sub_categories.*.name' => 'required|unique:shop_sub_categories',
            'shop_sub_categories.*.shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ]);

        $shopSubCategories=array();
        foreach ($validatedData['shop_sub_categories'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            $data['shop_category_id'] = $this->getShopCategoryId($data['shop_category_slug']);
            $subCategory=ShopSubCategory::create($data);
            array_push($shopSubCategories, $subCategory->load('shopCategory'));
        }

        return response()->json($shopSubCategories, 201);
    }
}
