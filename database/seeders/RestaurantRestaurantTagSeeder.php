<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\RestaurantTag;
use Illuminate\Database\Seeder;

class RestaurantRestaurantTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $restaurants = Restaurant::all();
        $restaurantTags = RestaurantTag::pluck('id');

        foreach ($restaurants as $restaurant) {
            $restaurant->availableTags()->attach($restaurantTags->random(5)->all());
        }
    }
}
