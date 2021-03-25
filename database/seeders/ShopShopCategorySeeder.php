<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\ShopCategory;
use Illuminate\Database\Seeder;

class ShopShopCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shops = Shop::all();
        $shopCategories = ShopCategory::pluck('id');

        foreach ($shops as $shop) {
            $shop->availableCategories()->attach($shopCategories->random(2)->all());
        }
    }
}
