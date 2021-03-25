<?php

namespace Database\Seeders;

use App\Models\RestaurantTag;
use Illuminate\Database\Seeder;

class RestaurantTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RestaurantTag::factory()->count(100)->create();
    }
}
