<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\ProductVariationValue;

class ProductVariationValueSeeder extends Seeder
{
     use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $productVariationVlaues = [
            [
                "name" => "ProductVariationValue1",
                "value" => "Value1",
                "slug" => $this->generateUniqueSlug(),
                "price" => 3000,
                "product_variation_id" => 1
            ],
            [
                "name" => "ProductVariationValue2",
                "value" => "Value2",
                "slug" => $this->generateUniqueSlug(),
                "price" => 4000,
                "product_variation_id" => 2
            ],
        ];

        foreach ($productVariationVlaues as $productVariationVlaue) {
            ProductVariationValue::create($productVariationVlaue);
        }
    }
}