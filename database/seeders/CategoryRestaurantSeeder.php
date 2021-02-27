<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;

class CategoryRestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $restaurants = Restaurant::all();
        $restaurantCategories = RestaurantCategory::pluck('id');

        foreach ($restaurants as $restaurant) {
            $restaurant->availableCategories()->attach($restaurantCategories->random(8)->all());
        }
    }
}
