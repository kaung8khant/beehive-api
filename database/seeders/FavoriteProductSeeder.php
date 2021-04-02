<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;

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
