<?php

namespace Database\Seeders;

use App\Models\RestaurantTag;
use App\Models\ShopCategory;
use App\Models\ShopTag;
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
        $this->call(RestaurantTagSeeder::class);
        $this->call(ShopTagSeeder::class);
        $this->call(RestaurantCategorySeeder::class);
        $this->call(ShopCategorySeeder::class);
        $this->call(SubCategorySeeder::class);
    }
}
