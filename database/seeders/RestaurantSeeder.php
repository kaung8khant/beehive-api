<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;

class RestaurantSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Restaurant::factory()->count(20)->has(RestaurantBranch::factory()->count(3))->create();
    }
}
