<?php

namespace Database\Seeders;

use App\Models\Shop;
use Illuminate\Database\Seeder;

class TagShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shop = Shop::find(1);
        $tagIdArrays = [1,2];
        foreach ($tagIdArrays as $id) {
            $shop->shop_tags()->attach($id);
        }
    }
}
