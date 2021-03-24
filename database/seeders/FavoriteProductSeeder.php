<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Customer;

class FavoriteProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customer = Customer::find(1);
        $productId = Product::find(1)->id;

        $customer->favoriteProducts()->attach($productId);
    }
}
