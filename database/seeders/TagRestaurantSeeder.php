<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\RestaurantTag;

class TagRestaurantSeeder extends Seeder
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
            $restaurant->restaurantTags()->attach($restaurantTags->random(20)->all());
        }
    }
}
