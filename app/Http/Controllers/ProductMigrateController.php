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
                $variant->update(['code' => sprintf('%02d', $key + 1)]);
            }
        }

        return 'done';
    }
}
