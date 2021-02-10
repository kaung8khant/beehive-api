<?php

namespace Database\Seeders;

use App\Models\Shop;
use Illuminate\Database\Seeder;

class CategoryShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shop = Shop::find(1);
        $categoryIdArrays = [1,2];
        foreach ($categoryIdArrays as $id) {
            $shop->shop_categories()->attach($id);
        }
    }
}
