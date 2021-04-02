<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\ProductVariationValue;
use Illuminate\Database\Seeder;

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
                'value' => 'Value1',
                'slug' => $this->generateUniqueSlug(),
                'price' => 3000,
                'product_variation_id' => 1,
            ],
            [
                'value' => 'Value2',
                'slug' => $this->generateUniqueSlug(),
                'price' => 4000,
                'product_variation_id' => 2,
            ],
        ];

        foreach ($productVariationVlaues as $productVariationVlaue) {
            ProductVariationValue::create($productVariationVlaue);
        }
    }
}
