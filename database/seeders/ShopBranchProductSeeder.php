<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ShopBranch;
use Illuminate\Database\Seeder;

class ShopBranchProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $branches = ShopBranch::all();

        // foreach ($branches as $b) {
        //     $products = Product::where('shop_id', $b->shop_id)->pluck('id');
        //     $b->availableProducts()->attach($products);
        // }
    }
}
