<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use App\Models\Shop;
use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/products",
     *      operationId="getProductLists",
     *      tags={"Products"},
     *      summary="Get list of products",
     *      description="Returns list of products",
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
        return Product::with('shop', 'shopCategory', 'brand', 'shopSubCategory')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/{path}/products",
     *      operationId="storeProduct",
     *      tags={"Products"},
     *      summary="Create a product",
     *      description="Returns newly created product",
     *      @OA\Parameter(
     *          name="path",
     *          description="Key of a requested setting",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string",
     *              enum= {"admin","vendor"},
     *              default= "admin",
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created product object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Product")
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
        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);

        if ($request->shop_sub_category_slug) {
            $subCategory = $this->getSubCategory($request->shop_sub_category_slug);
            $validatedData['shop_category_id'] = $subCategory->shopCategory->id;
            $validatedData['shop_sub_category_id'] = $subCategory->id;
        } else {
            $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);
        }

        if ($request->brand_slug) {
            $validatedData['brand_id'] = $this->getBrandId($request->brand_slug);
        }

        $product = Product::create($validatedData);
        $productId = $product->id;

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'products', $product->slug);
        }

        if ($request->cover_slug) {
            $this->updateFile($request->cover_slug, 'products', $product->slug);
        }

        if ($request->product_variations) {
            $this->createProductVariation($productId, $validatedData['product_variations']);
        }

        return response()->json($product->refresh()->load('shop', "productVariations"), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/{path}/products/{slug}",
     *      operationId="showProduct",
     *      tags={"Products"},
     *      summary="Get One Product",
     *      description="Returns a requested product",
     *      @OA\Parameter(
     *          name="path",
     *          description="Key of a requested setting",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string",
     *              enum= {"admin","vendor"},
     *              default= "admin",
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested product",
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
        $product = Product::with('shop', 'shopCategory', 'shopSubCategory', 'brand')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->where('slug', $slug)->firstOrFail();
        return response()->json($product, 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/{path}/products/{slug}",
     *      operationId="updateProduct",
     *      tags={"Products"},
     *      summary="Update a product",
     *      description="Update a requested product",
     *      @OA\Parameter(
     *          name="path",
     *          description="Key of a requested setting",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string",
     *              enum= {"admin","vendor"},
     *              default= "admin",
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a product",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New product data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Product")
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
        $product = Product::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);

        if ($request->shop_sub_category_slug) {
            $subCategory = $this->getSubCategory($request->shop_sub_category_slug);
            $validatedData['shop_category_id'] = $subCategory->shopCategory->id;
            $validatedData['shop_sub_category_id'] = $subCategory->id;
        } else {
            $validatedData['shop_category_id'] = $this->getShopCategoryId($request->shop_category_slug);
        }

        if ($request->brand_slug) {
            $validatedData['brand_id'] = $this->getBrandId($request->brand_slug);
        }
        $product->update($validatedData);

        $productId = $product->id;

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'products', $slug);
        }

        if ($request->cover_slug) {
            $this->updateFile($request->cover_slug, 'products', $slug);
        }

        if ($request->product_variations) {
            $product->productVariations()->delete();
            $this->createProductVariation($productId, $validatedData['product_variations']);
        }
        return response()->json($product, 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/{path}/products/{slug}",
     *      operationId="deleteProduct",
     *      tags={"Products"},
     *      summary="Delete One Product",
     *      description="Delete one specific product",
     *      @OA\Parameter(
     *          name="path",
     *          description="Key of a requested setting",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string",
     *              enum= {"admin","vendor"},
     *              default= "admin",
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested product",
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
        $product = Product::where('slug', $slug)->firstOrFail();

        foreach ($product->images as $image) {
            $this->deleteFile($image->slug);
        }

        $product->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|max:99999999',
            'tax' => 'required|numeric',
            'is_enable' => 'required|boolean',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
            'shop_sub_category_slug' => 'nullable|exists:App\Models\ShopSubCategory,slug',
            'brand_slug' => 'nullable|exists:App\Models\Brand,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'cover_slug' => 'nullable|exists:App\Models\File,slug',

            'product_variations' => 'nullable|array',
            'product_variations.*.slug' => '',
            'product_variations.*.name' => 'required|string',

            'product_variations.*.product_variation_values' => 'required|array',
            'product_variations.*.product_variation_values.*.value' => 'required|string',
            'product_variations.*.product_variation_values.*.price' => 'required|numeric',
            'product_variations.*.product_variation_values.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:products';
        }

        return $params;
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->first()->id;
    }

    private function getShopCategoryId($slug)
    {
        return ShopCategory::where('slug', $slug)->first()->id;
    }

    private function getSubCategory($slug)
    {
        return ShopSubCategory::where('slug', $slug)->first();
    }

    /**
     * Display a listing of the products by one shop.
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/shops/{slug}/products",
     *      operationId="getProductsByShop",
     *      tags={"Products"},
     *      summary="Get Products By Shop",
     *      description="Returns requested list of products",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the shop",
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
    public function getProductsByShop(Request $request, $slug)
    {
        return Product::with('shop', 'shopCategory', 'shopSubCategory', 'brand')
            ->with('productVariations')->with('productVariations.productVariationValues')
            ->whereHas('shop', function ($q) use ($slug) {
                $q->where('slug', $slug);
            })->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })->paginate(10);
    }

    /**
     * Display available products by one shop branch.
     */
    public function getAvailableProductsByShopBranch(Request $request, $slug)
    {
        return Product::with('shopCategory', 'brand')->whereHas('shop_branches', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    private function getBrandId($slug)
    {
        return Brand::where('slug', $slug)->first()->id;
    }

    private function createProductVariation($productId, $productVariations)
    {
        foreach ($productVariations as $variation) {
            $variation['slug'] = $this->generateUniqueSlug();
            $variation['product_id'] = $productId;
            $productVariation = ProductVariation::create($variation);
            $variationId = $productVariation->id;
            $this->createVariationValues($variationId, $variation['product_variation_values']);
        }
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

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/products/toggle-enable/{slug}",
     *      operationId="enableProduct",
     *      tags={"Products"},
     *      summary="Enable Product",
     *      description="Enable a product",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the Product",
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
        $product = Product::where('slug', $slug)->firstOrFail();
        $product->is_enable = !$product->is_enable;
        $product->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    public function multiToggleEnable(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Product,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $product->is_enable = !$product->is_enable;
            $product->save();
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/brands/{slug}/products",
     *      operationId="getProductsByBrand",
     *      tags={"Products"},
     *      summary="Get Products By Brand",
     *      description="Returns requested list of products",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the Brand",
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
    public function getProductsByBrand(Request $request, $slug)
    {
        return Product::with('shop', 'shopCategory')->whereHas('brand', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function getProductsByCategory(Request $request, $slug)
    {
        return Product::with('shop', 'shopCategory')->whereHas('shopCategory', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate([
            'products' => 'nullable|array',
            'products.*.name' => 'required|string',
            'products.*.description' => 'nullable|string',
            'products.*.price' => 'required|max:99999999',
            'products.*.tax' => 'required|numeric',
            'products.*.is_enable' => 'required|boolean',
            'products.*.shop_slug' => 'required|exists:App\Models\Shop,slug',
            'products.*.shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
            'products.*.shop_sub_category_slug' => 'nullable|exists:App\Models\ShopSubCategory,slug',
            'products.*.brand_slug' => 'nullable|exists:App\Models\Brand,slug',
        ]);

        foreach ($validatedData['products'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            $data['shop_id'] = $this->getShopId($data['shop_slug']);

            if (isset($data['shop_sub_category_slug'])) {
                $subCategory = $this->getSubCategory($data['shop_sub_category_slug']);
                $data['shop_category_id'] = $subCategory->shopCategory->id;
                $data['shop_sub_category_id'] = $subCategory->id;
            } else {
                $data['shop_category_id'] = $this->getShopCategoryId($data['shop_category_slug']);
            }

            if (isset($data['brand_slug'])) {
                $data['brand_id'] = $this->getBrandId($data['brand_slug']);
            }

            Product::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
