<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductMigrateController extends Controller
{
    public function migrate()
    {
        $products = Product::with([
            'productVariants',
        ])
            ->skip(request('from'))
            ->take(request('limit'))
            ->get();

        foreach ($products as $product) {
            foreach ($product->productVariants as $key => $variant) {
                if (count($variant->variant) === 1 && $variant->variant[0]['name'] === 'default') {
                    $variant->update(['code' => sprintf('%02d', $key)]);
                } else {
                    $variant->update(['code' => sprintf('%02d', $key + 1)]);
                }
            }
        }

        return 'done';
    }
}
