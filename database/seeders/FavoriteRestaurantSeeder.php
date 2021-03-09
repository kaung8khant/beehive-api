<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\Customer;

class FavoriteRestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customer = Customer::find(1);
        $restaurantId = Restaurant::find(1)->value('id');

        $customer->favoriteRestaurants()->attach($restaurantId);
    }
}