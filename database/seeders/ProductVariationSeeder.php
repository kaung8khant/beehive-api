<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\ProductVariation;

class ProductVariationSeeder extends Seeder
{
     use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $productVariations = [
            [
                "name" => "ProductVariation1",
                "description" => "Description",
                "slug" => $this->generateUniqueSlug(),
                "product_id" => 1

            ],
            [
                "name" => "ProductVariation2",
                "description" => "Description",
                "slug" => $this->generateUniqueSlug(),
                "product_id" => 2

            ],
        ];

        foreach ($productVariations as $productVariation) {
            ProductVariation::create($productVariation);
        }
    }
}
