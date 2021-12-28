<?php

namespace App\Http\Controllers\Admin;

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
        return $this->productRepository->find($slug)->load(['shop', 'shopCategory', 'shopSubCategory', 'brand', 'productVariants']);
    }

    public function store(ProductCreateRequest $request)
    {
        $product = $this->productRepository->create($request->validated())->refresh()->load(['shop', 'productVariants']);
        return response()->json($product, 201);
    }

    public function update(ProductUpdateRequest $request, $slug)
    {
        return $this->productRepository->update($slug, $request->validated());
    }

    public function destroy($slug)
    {
        return $this->productRepository->delete($slug);
    }

    public function toggleEnable($slug)
    {
        return $this->productRepository->toggleEnable($slug);
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
            'shop' => fn ($query) => $query->select('id', 'slug', 'name'),
            'shopCategory' => fn ($query) => $query->select('id', 'shop_main_category_id', 'code', 'slug', 'name'),
            'shopCategory.shopMainCategory' => fn ($query) => $query->select('id', 'code', 'slug', 'name'),
            'brand' => fn ($query) => $query->select('id', 'code', 'slug', 'name'),
            'productVariants' => fn ($query) => $query
                ->select('product_id', 'slug', 'variant', 'price', 'discount', 'vendor_price', 'tax', 'code')
                ->where('is_enable', 1)
                ->orderBy('price', 'asc'),
        ]);

        foreach ($products as $product) {
            $product->makeHidden(['description', 'created_by', 'updated_by', 'covers']);
            $product->shop->setAppends([]);
            $product->shopCategory->setAppends([]);

            if ($product->shopCategory->shopMainCategory) {
                $product->shopCategory->shopMainCategory->setAppends([]);
            }

            if ($product->brand) {
                $product->brand->setAppends([]);
            }
        }
    }
}
