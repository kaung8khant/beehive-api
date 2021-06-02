<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
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

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('products', 'id', $request->by ? $request->by : 'desc', $request->order);

        $products = Product::with('shop', 'shopCategory', 'brand', 'shopSubCategory', 'productVariations', 'productVariations.productVariationValues')
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $products = $products->where('is_enable', $request->is_enable)
                ->whereHas('shop', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $products->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

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

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'products', $product->slug);
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'products', $product->slug);
            }
        }

        if ($request->product_variations) {
            $this->createProductVariation($product->id, $validatedData['product_variations']);
        }

        return response()->json($product->refresh()->load('shop', "productVariations"), 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariations', 'productVariations.productVariationValues'), 200);
    }

    public function update(Request $request, Product $product)
    {
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

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'products', $product->slug);
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'products', $product->slug);
            }
        }

        if ($request->product_variations) {
            $product->productVariations()->delete();
            $this->createProductVariation($product->id, $validatedData['product_variations']);
        } else {
            $product->productVariations()->delete();
        }

        return response()->json($product, 200);
    }

    public function destroy(Product $product)
    {
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
            'discount' => 'required|numeric',
            'is_enable' => 'required|boolean',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
            'shop_sub_category_slug' => 'nullable|exists:App\Models\ShopSubCategory,slug',
            'brand_slug' => 'nullable|exists:App\Models\Brand,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',

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

    public function getProductsByShop(Request $request, Shop $shop)
    {
        $sorting = CollectionHelper::getSorting('products', 'id', $request->by ? $request->by : 'desc', $request->order);

        $products = Product::with('shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariations', 'productVariations.productVariationValues')
            ->where('shop_id', $shop->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $products = $products->where('is_enable', $request->is_enable)
                ->whereHas('shop', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $products->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
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

    public function toggleEnable(Product $product)
    {
        $product->update(['is_enable' => !$product->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleStatusUpdate(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Product,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $product->update(['is_enable' => $request->is_enable]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleDelete(Request $request)
    {
        $validatedData = $request->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Product,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();

            foreach ($product->images as $image) {
                $this->deleteFile($image->slug);
            }

            $product->delete();
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function getProductsByBrand(Request $request, Brand $brand)
    {
        $sorting = CollectionHelper::getSorting('products', 'id', $request->by ? $request->by : 'desc', $request->order);

        $products = Product::with('shop', 'shopCategory')
            ->where('brand_id', $brand->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $products = $products->where('is_enable', $request->is_enable)
                ->whereHas('shop', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $products->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function getProductsByCategory(Request $request, ShopCategory $shopCategory)
    {
        $sorting = CollectionHelper::getSorting('products', 'id', $request->by ? $request->by : 'desc', $request->order);

        $products = Product::with('shop', 'shopCategory')
            ->where('shop_category_id', $shopCategory->id)
            ->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            });

        if (isset($request->is_enable)) {
            $products = $products->where('is_enable', $request->is_enable)
                ->whereHas('shop', function ($query) use ($request) {
                    $query->where('is_enable', $request->is_enable);
                });
        }

        return $products->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }
}
