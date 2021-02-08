<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CitySeeder::class);
        $this->call(TownshipSeeder::class);
        $this->call(TagSeeder::class);
        $this->call(RestaurantCategorySeeder::class);
        $this->call(StoreCategorySeeder::class);
        $this->call(SubCategorySeeder::class);
    }
}
