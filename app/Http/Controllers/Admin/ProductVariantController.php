<?php

namespace App\Http\Controllers\Admin;

use App\Events\DataChanged;
use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductVariantController extends Controller
{
    use FileHelper, StringHelper;

    private $user;

    public function __construct()
    {
        if (Auth::guard('users')->check()) {
            $this->user = Auth::guard('users')->user();
        }
    }

    public function updateVariants(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string',
            'variants.*.values' => 'required|array',

            'product_variants' => 'required',
            'product_variants.*.slug' => 'nullable|exists:App\Models\ProductVariant,slug',
            'product_variants.*.variant' => 'required',
            'product_variants.*.price' => 'required|numeric',
            'product_variants.*.tax' => 'required|numeric',
            'product_variants.*.discount' => 'required|numeric',
            'product_variants.*.is_enable' => 'required|boolean',
            'product_variants.*.image_slug' => 'nullable|exists:App\Models\File,slug',
        ]);

        if (isset($validatedData['variants'])) {
            $product->update([
                'variants' => $validatedData['variants'],
            ]);

            DataChanged::dispatch($this->user, 'update', 'products', $product->slug, $request->url(), 'success', $validatedData['variants']);
        }

        $variantSlugs = $product->productVariants->pluck('slug');

        foreach ($validatedData['product_variants'] as $data) {
            if (isset($data['slug']) && $variantSlugs->contains($data['slug'])) {
                $productVariant = ProductVariant::where('slug', $data['slug'])->first();
                $productVariant->update($data);

                $arrKey = $variantSlugs->search($data['slug']);
                unset($variantSlugs[$arrKey]);

                DataChanged::dispatch($this->user, 'update', 'product_variants', $data['slug'], $request->url(), 'success', $data);
            } else {
                $data['product_id'] = $product->id;
                $data['slug'] = $this->generateUniqueSlug();

                ProductVariant::create($data);
                DataChanged::dispatch($this->user, 'create', 'product_variants', $data['slug'], $request->url(), 'success', $data);
            }

            if (isset($data['image_slug'])) {
                $this->updateFile($data['image_slug'], 'product_variants', $data['slug']);
            }
        }

        foreach ($variantSlugs as $slug) {
            $productVariant = ProductVariant::where('slug', $slug)->first();
            $productVariant->delete();
        }

        return response()->json($product->refresh()->load('productVariants'), 200);
    }

    public function toggleEnable(Request $request, ProductVariant $productVariant)
    {
        $productVariant->update(['is_enable' => !$productVariant->is_enable]);

        $status = $productVariant->is_enable ? 'enable' : 'disable';
        DataChanged::dispatch($this->user, $status, 'product_variants', $productVariant->slug, $request->url(), 'success');

        return response()->json(['message' => 'Success.'], 200);
    }
}
