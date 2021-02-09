<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class TagRestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $restaurant = Restaurant::find(1);
        $tagIdArrays = [1,2];
        foreach ($tagIdArrays as $id) {
            $restaurant->restaurant_tags()->attach($id);
        }
    }
}
