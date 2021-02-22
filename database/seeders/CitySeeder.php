<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\City;
use App\Models\Township;

class CitySeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        City::factory()->count(3)->has(Township::factory()->count(3))->create();
        City::factory()->count(3)->has(Township::factory()->count(5))->create();
        City::factory()->count(10)->create();
    }
}
