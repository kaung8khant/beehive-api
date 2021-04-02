<?php

namespace Database\Seeders;

use App\Models\Shop;
use Illuminate\Database\Seeder;

// use App\Models\ShopTag;

class ShopShopTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shops = Shop::all();
        $shopTags = [1, 2];

        foreach ($shops as $shop) {
            $shop->availableTags()->attach($shopTags);
        }
    }
}
