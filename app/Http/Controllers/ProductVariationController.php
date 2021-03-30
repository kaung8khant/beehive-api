<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/product-variations",
     *      operationId="getProductVariationLists",
     *      tags={"Product Variations"},
     *      summary="Get list of product variations",
     *      description="Returns list of product variations",
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
        return ProductVariation::with('product')
            ->where('name', 'LIKE', '%' . $request->filterr . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/product-variations",
     *      operationId="storeProductVariation",
     *      tags={"Product Variations"},
     *      summary="Create a product variation",
     *      description="Returns newly created product variation",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created product variation object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ProductVariation")
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
            'product_variations' => 'required|array',
            'product_variations.*.slug' => '',
            'product_variations.*.name' => 'required|string',
            'product_slug' => 'required|exists:App\Models\Product,slug',

            'product_variations.*.product_variation_values' => 'required|array',
            'product_variations.*.product_variation_values.*.value' => 'required|string',
            'product_variations.*.product_variation_values.*.price' => 'required|numeric',
            'product_variations.*.product_variation_values.*.image_slug' => 'nullable|exists:App\Models\File,slug',

        ]);

        $productId = $this->getProductId($request->product_slug);
        $productVariations = $validatedData["product_variations"];

        foreach ($productVariations as $variation) {
            $variation['slug'] = $this->generateUniqueSlug();
            $variation['product_id'] = $productId;
            $productVariation = ProductVariation::create($variation);

            $variationId = $productVariation->id;
            $this->createVariationValues($variationId, $variation['product_variation_values']);
        }
        return response()->json($productVariation->load('product', 'productVariationValues'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/product-variations/{slug}",
     *      operationId="showProductVariation",
     *      tags={"Product Variations"},
     *      summary="Get One Product Variation",
     *      description="Returns a requested product variation",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested product variation",
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
        $productVariation = ProductVariation::with('product', 'productVariationValues')->where('slug', $slug)->firstOrFail();
        return response()->json($productVariation, 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/product-variations/{slug}",
     *      operationId="updateProductVariation",
     *      tags={"Product Variations"},
     *      summary="Update a product variation",
     *      description="Update a requested product variation",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a product variation",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New product variation data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/ProductVariation")
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
        $productVariation = ProductVariation::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => 'required|string',
            'product_slug' => 'required|exists:App\Models\Product,slug',
        ]);

        $validatedData['product_id'] = $this->getProductId($request->product_slug);

        $productVariation->update($validatedData);
        return response()->json($productVariation->load('product'), 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/product-variations/{slug}",
     *      operationId="deleteProductVariation",
     *      tags={"Product Variations"},
     *      summary="Delete One Product Variation",
     *      description="Delete one specific product variation",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested product variation",
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
        ProductVariation::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/products/{slug}/product-variations",
     *      operationId="getProductVariationsByProduct",
     *      tags={"Product Variations"},
     *      summary="Get Product Variations By Product",
     *      description="Returns requested list of product variations",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the Product",
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
    public function getProductVariationsByProduct($slug, Request $request)
    {
        return ProductVariation::with('productVariationValues')
            ->whereHas('product', function ($q) use ($slug) {
                $q->where('slug', $slug);
            })->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    private function createVariationValues($variationId, $variationValues)
    {
        foreach ($variationValues as $variationValue) {
            $variationValue['slug'] = $this->generateUniqueSlug();
            $variationValue['product_variation_id'] = $variationId;
            ProductVariationValue::create($variationValue);
            if (!empty($variationValue['image_slug'])) {
                $this->updateFile($variationValue['image_slug'], 'product_variation_values', $variationValue['slug']);
            }
        }
    }
}
