<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

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
        $restaurantId = Restaurant::find(1)->id;

        $customer->favoriteRestaurants()->attach($restaurantId);
    }
}
