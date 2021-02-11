<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = [
            [
                "name" => "Product1",
                "name_mm" => "Product1_mm",
                "description" => "Description",
                "description_mm" => "Description_MM",
                "price" => 30000,
                "slug" => $this->generateUniqueSlug(),
                "shop_id" => 1,
                "shop_category_id"=>1

            ],
            [
                "name" => "Product2",
                "name_mm" => "Product2_mm",
                "description" => "Description",
                "description_mm" => "Description_MM",
                "price" => 60000,
                "slug" => $this->generateUniqueSlug(),
                "shop_id" => 1,
                "shop_category_id"=>2

            ],
        ];
        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
