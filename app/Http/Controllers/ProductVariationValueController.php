<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Illuminate\Http\Request;

class ProductVariationValueController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/product-variation-values",
     *      operationId="getProductVariationValueLists",
     *      tags={"Product Variation Values"},
     *      summary="Get list of product variaiton values",
     *      description="Returns list of product variation values",
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
        return ProductVariationValue::with('productVariation')
            ->where('value', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/product-variation-values",
     *      operationId="storeProductVariationValue",
     *      tags={"Product Variation Values"},
     *      summary="Create list of product variation value",
     *      description="Returns list of newly created product variation value",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created product variation value list of object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ProductVariationValue")
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

        $validatedData = $request->validate($this->getParamsToValidate(true));
        $validatedData['product_variation_id'] = $this->getProductVariationId($request->product_variation_slug);

        $productVariationValue = ProductVariationValue::create($validatedData);

        if (!empty($request->image_slug)) {
            $this->updateFile($request->image_slug, 'product_variation_values', $productVariationValue->slug);
        }

        return response()->json($productVariationValue->load('productVariation'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/product-variation-values/{slug}",
     *      operationId="showProductVariationValue",
     *      tags={"Product Variation Values"},
     *      summary="Get One Product Variation Value",
     *      description="Returns a requested product variation value",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested product variation value",
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
    public function show(ProductVariationValue $productVariationValue)
    {
        return response()->json($productVariationValue->with('productVariation'), 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/product-variation-values/{slug}",
     *      operationId="updateProductVariationValue",
     *      tags={"Products"},
     *      summary="Update a product variation value",
     *      description="Update a requested product variation value",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a product variation value",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New product variation value data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ProductVariationValue")
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
    public function update(Request $request, ProductVariationValue $productVariationValue)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['product_variation_id'] = $this->getProductVariationId($request->product_variation_slug);

        $productVariationValue->update($request->all());

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'product_variation_values', $productVariationValue->slug);
        }

        return response()->json($productVariationValue->load('productVariation'), 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/product-variation-values/{slug}",
     *      operationId="deleteProductVariationValue",
     *      tags={"Product Variation Values"},
     *      summary="Delete One Product variation value",
     *      description="Delete one specific product variation value",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested product variation value",
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
    public function destroy(ProductVariationValue $productVariationValue)
    {
        foreach ($productVariationValue->images as $image) {
            $this->deleteFile($image->slug);
        }

        $productVariationValue->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'value' => 'required',
            'price' => 'required|numeric',
            'product_variation_slug' => 'required|exists:App\Models\ProductVariation,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:product_variation_values';
        }

        return $params;
    }

    private function getProductVariationId($slug)
    {
        return ProductVariation::where('slug', $slug)->first()->id;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/product-variations/{productVariation}/product-variation-values",
     *      operationId="getVariationValuesByVariation",
     *      tags={"Product Variation Values"},
     *      summary="Get Product Variation Values By Product variation",
     *      description="Returns requested list of product variation values",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the product variation",
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
    public function getVariationValuesByVariation(Request $request, ProductVariation $productVariation)
    {
        return ProductVariationValue::where('product_variation_id', $productVariation->id)
            ->where(function ($query) use ($request) {
                return $query->where('value', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
    }
}
