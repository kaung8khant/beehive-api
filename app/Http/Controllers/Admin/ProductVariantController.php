<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        return ProductVariant::where('slug', $request->filter)
            ->paginate(10);
    }

    public function update(Request $request, ProductVariant $productVariant)
    {
        $validatedData = $request->validate([
            'price' => 'required|numeric',
            'vendor_price' => 'required|numeric',
            'product_slug' => 'required|exists:App\Models\Product,slug',
        ]);
        $validatedData['product_id'] = $this->getProductId($request->product_slug);

        $productVariant->update($validatedData);

        return response()->json($productVariant, 200);
    }

    private function getProductId($slug)
    {
        return Product::where('slug', $slug)->first()->id;
    }

    public function show(ProductVariant $productVariant)
    {
        return response()->json($productVariant, 200);
    }

    public function destroy(ProductVariant $productVariant)
    {
        $productVariant->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
