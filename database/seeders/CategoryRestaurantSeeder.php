<?php

namespace Database\Seeders;

use App\Models\Restaurant;
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
        $restaurant = Restaurant::find(1);
        $categoryIdArrays = [1,2];
        foreach ($categoryIdArrays as $id) {
            $restaurant->restaurant_categories()->attach($id);
        }
    }
}
