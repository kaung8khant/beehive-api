<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Illuminate\Database\Seeder;

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
        $restaurantCategories = RestaurantCategory::pluck('id')->toArray();

        foreach ($restaurants as $restaurant) {
            $restaurant->restaurant_categories()->attach(array_rand($restaurantCategories, 5));
        }
    }
}
