<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Illuminate\Database\Seeder;

class RestaurantRestaurantCategorySeeder extends Seeder
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
