<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationValue;
use Illuminate\Http\Request;

class ProductVariationController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        return ProductVariation::with('product')
            ->where('name', 'LIKE', '%' . $request->filterr . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

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

        $productVariations = $validatedData['product_variations'];

        foreach ($productVariations as $variation) {
            $variation['slug'] = $this->generateUniqueSlug();
            $variation['product_id'] = $this->getProductId($request->product_slug);
            $productVariation = ProductVariation::create($variation);

            $variationId = $productVariation->id;
            $this->createVariationValues($variationId, $variation['product_variation_values']);
        }

        return response()->json($productVariation->load('product', 'productVariationValues'), 201);
    }

    public function show(ProductVariation $productVariation)
    {
        return response()->json($productVariation->load('product', 'productVariationValues'), 200);
    }

    public function update(Request $request, ProductVariation $productVariation)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'product_slug' => 'required|exists:App\Models\Product,slug',
        ]);

        $validatedData['product_id'] = $this->getProductId($request->product_slug);
        $productVariation->update($validatedData);

        return response()->json($productVariation->load('product'), 200);
    }

    public function destroy(ProductVariation $productVariation)
    {
        $productVariation->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }

    public function getProductVariationsByProduct(Request $request, Product $product)
    {
        return ProductVariation::with('productVariationValues')
            ->where('product_id', $product->id)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
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
