<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\Customer;

class FavoriteShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customer = Customer::find(1);
        $shopId = Shop::find(1)->value('id');

        $customer->shops()->attach($shopId);
    }
}
