<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CacheHelper;
use App\Helpers\CollectionHelper;
use App\Http\Controllers\Controller;
use App\Repositories\Shop\Product\ProductCreateRequest;
use App\Repositories\Shop\Product\ProductRepositoryInterface;
use App\Repositories\Shop\Product\ProductUpdateRequest;

class ProductController extends Controller
{
    private $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function index()
    {
        $products = $this->productRepository->all();
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function show($slug)
    {
        return $this->productRepository->find($slug)->load(['shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariations', 'productVariations.productVariationValues', 'productVariants']);
    }

    public function store(ProductCreateRequest $request)
    {
        $validatedData = $this->prepareProductData($request->validated());
        $product = $this->productRepository->create($validatedData)->refresh()->load(['shop', 'productVariants']);
        return response()->json($product, 201);
    }

    public function update(ProductUpdateRequest $request, $slug)
    {
        $validatedData = $this->prepareProductData($request->validated());
        return $this->productRepository->update($slug, $validatedData);
    }

    public function destroy($slug)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $this->productRepository->delete($slug);
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function prepareProductData($validatedData)
    {
        $validatedData['shop_id'] = CacheHelper::getShopIdBySlug($validatedData['shop_slug']);
        $validatedData['shop_category_id'] = CacheHelper::getShopCategoryIdBySlug($validatedData['shop_category_slug']);

        if (isset($validatedData['shop_sub_category_slug'])) {
            $validatedData['shop_sub_category_id'] = CacheHelper::getShopSubCategoryIdBySlug($validatedData['shop_sub_category_slug']);
        }

        if (isset($validatedData['brand_slug'])) {
            $validatedData['brand_id'] = CacheHelper::getBrandIdBySlug($validatedData['brand_slug']);
        }

        return $validatedData;
    }

    public function toggleEnable($slug)
    {
        $product = $this->productRepository->find($slug);
        $product->update(['is_enable' => !$product->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleStatusUpdate()
    {
        $validatedData = request()->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Product,slug',
            'is_enable' => 'required|boolean',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $this->productRepository->update($slug, ['is_enable' => request('is_enable')]);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function multipleDelete()
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $validatedData = request()->validate([
            'slugs' => 'required|array',
            'slugs.*' => 'required|exists:App\Models\Product,slug',
        ]);

        foreach ($validatedData['slugs'] as $slug) {
            $this->productRepository->delete($slug);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function getProductsByShop($slug)
    {
        $products = $this->productRepository->getAllByShop($slug);
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function getProductsByBrand($slug)
    {
        $products = $this->productRepository->getAllByBrand($slug);
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function getProductsByCategory($slug)
    {
        $products = $this->productRepository->getAllByCategory($slug);
        $this->optimizeProducts($products);
        return CollectionHelper::removePaginateLinks($products);
    }

    public function updateSearchIndex($slug)
    {
        $product = $this->productRepository->update($slug, request()->validate([
            'search_index' => 'required|numeric',
        ]));

        return response()->json($product->load(['shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariations', 'productVariations.productVariationValues', 'productVariants']), 200);
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
            'productVariants' => function ($query) {
                $query->select('product_id', 'slug', 'variant', 'price', 'discount', 'vendor_price', 'tax')
                    ->where('is_enable', 1)
                    ->orderBy('price', 'asc');
            },
        ]);

        foreach ($products as $product) {
            $product->makeHidden(['description', 'created_by', 'updated_by', 'covers']);
            $product->shop->setAppends([]);
            $product->shopCategory->setAppends([]);

            if ($product->brand) {
                $product->brand->setAppends([]);
            }
        }
    }
}
