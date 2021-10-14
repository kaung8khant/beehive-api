<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CacheHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $products = Product::search($request->filter);

        if (isset($request->is_enable)) {
            $productIds = Product::whereHas('shop', function ($query) use ($request) {
                $query->where('is_enable', $request->is_enable);
            })->pluck('id')->toArray();

            $products = $products->where('is_enable', $request->is_enable)
                ->whereIn('id', $productIds);
        }

        $products = $products->paginate(10);
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $validatedData = $this->validateRequest($request, true);

        $product = Product::create($validatedData);

        if ($request->image_slugs) {
            foreach ($request->image_slugs as $imageSlug) {
                $this->updateFile($imageSlug, 'products', $product->slug);
            }
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'products', $product->slug);
            }
        }

        if (isset($validatedData['product_variants'])) {
            $this->createProductVariants($product->id, $validatedData['product_variants']);
        }

        return response()->json($product->refresh()->load('shop', 'productVariants'), 201);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariations', 'productVariations.productVariationValues', 'productVariants'), 200);
    }

    public function update(Request $request, Product $product)
    {
        $validatedData = $this->validateRequest($request);
        $product->update($validatedData);

        if ($request->image_slugs) {
            foreach ($request->image_slugs as $imageSlug) {
                $this->updateFile($imageSlug, 'products', $product->slug);
            }
        }

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'products', $product->slug);
            }
        }

        if (isset($validatedData['product_variants'])) {
            $product->productVariants()->delete();
            $this->createProductVariants($product->id, $validatedData['product_variants']);
        } else {
            $product->productVariants()->delete();
        }

        return response()->json($product, 200);
    }

    public function destroy(Product $product)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        foreach ($product->images as $image) {
            $this->deleteFile($image->slug);
        }

        $product->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function validateRequest($request, $slug = false)
    {
        $params = [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'is_enable' => 'required|boolean',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
            'shop_sub_category_slug' => 'nullable|exists:App\Models\ShopSubCategory,slug',
            'brand_slug' => 'nullable|exists:App\Models\Brand,slug',
            'image_slugs' => 'nullable|array',
            'image_slugs.*' => 'nullable|exists:App\Models\File,slug',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',

            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.values' => 'required|array',

            'product_variants' => 'required_with:variants',
            'product_variants.*.variant' => 'required',
            'product_variants.*.price' => 'required|numeric',
            'product_variants.*.vendor_price' => 'required|numeric',
            'product_variants.*.tax' => 'required|numeric',
            'product_variants.*.discount' => 'required|numeric',
            'product_variants.*.is_enable' => 'required|boolean',
            'product_variants.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:products';
        }

        $validatedData = $request->validate($params);

        $validatedData['shop_id'] = CacheHelper::getShopIdBySlug($request->shop_slug);
        $validatedData['shop_category_id'] = CacheHelper::getShopCategoryIdBySlug($request->shop_category_slug);

        if ($request->shop_sub_category_slug) {
            $validatedData['shop_sub_category_id'] = CacheHelper::getShopSubCategoryIdBySlug($request->shop_sub_category_slug);
        }

        if ($request->brand_slug) {
            $validatedData['brand_id'] = CacheHelper::getBrandIdBySlug($request->brand_slug);
        }

        return $validatedData;
    }

    private function createProductVariants($productId, $productVariants)
    {
        foreach ($productVariants as $variant) {
            $variant['product_id'] = $productId;
            $variant['slug'] = $this->generateUniqueSlug();

            ProductVariant::create($variant);

            if (isset($variant['image_slug'])) {
                $this->updateFile($variant['image_slug'], 'product_variants', $variant['slug']);
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
        return response()->json(['message' => 'Permission denied.'], 403);

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

    public function getProductsByShop(Request $request, Shop $shop)
    {
        $products = Product::search($request->filter)->where('shop_id', $shop->id);

        if (isset($request->is_enable)) {
            $productIds = Product::whereHas('shop', function ($query) use ($request) {
                $query->where('is_enable', $request->is_enable);
            })->pluck('id')->toArray();

            $products = $products->where('is_enable', $request->is_enable)
                ->whereIn('id', $productIds);
        }

        $products = $products->paginate(10);
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function getProductsByBrand(Request $request, Brand $brand)
    {
        $products = Product::search($request->filter)->where('brand_id', $brand->id);

        if (isset($request->is_enable)) {
            $productIds = Product::whereHas('shop', function ($query) use ($request) {
                $query->where('is_enable', $request->is_enable);
            })->pluck('id')->toArray();

            $products = $products->where('is_enable', $request->is_enable)
                ->whereIn('id', $productIds);
        }

        $products = $products->paginate(10);
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function getProductsByCategory(Request $request, ShopCategory $shopCategory)
    {
        $products = Product::search($request->filter)->where('shop_category_id', $shopCategory->id);

        if (isset($request->is_enable)) {
            $productIds = Product::whereHas('shop', function ($query) use ($request) {
                $query->where('is_enable', $request->is_enable);
            })->pluck('id')->toArray();

            $products = $products->where('is_enable', $request->is_enable)
                ->whereIn('id', $productIds);
        }

        $products = $products->paginate(10);
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function updateSearchIndex(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'search_index' => 'required|numeric',
        ]);

        $product->update($validatedData);

        return response()->json($product->load('shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariations', 'productVariations.productVariationValues', 'productVariants'), 200);
    }

    private function optimizeProducts($products)
    {
        $products->load([
            'shop' => function ($query) {
                $query->select('id', 'slug', 'name');
            },
            'shopCategory' => function ($query) {
                $query->select('id', 'slug', 'name');
            },
            'brand' => function ($query) {
                $query->select('id', 'slug', 'name');
            },
        ]);

        foreach ($products as $product) {
            $product->makeHidden(['id', 'description', 'variants', 'created_by', 'updated_by']);
            $product->shop->makeHidden(['id'])->setAppends([]);
            $product->shopCategory->makeHidden(['id'])->setAppends([]);

            if ($product->brand) {
                $product->brand->makeHidden(['id'])->setAppends([]);
            }

            $product->product_variants = $product->productVariants()
                ->select('price', 'discount', 'vendor_price')
                ->where('is_enable', 1)
                ->orderBy('price', 'asc')
                ->limit(1)
                ->get();
        }
    }
}
