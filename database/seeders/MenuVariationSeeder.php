<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;

class MenuVariationSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MenuVariation::factory()->count(100)->has(MenuVariationValue::factory()->count(1))->create();
        MenuVariation::factory()->count(100)->has(MenuVariationValue::factory()->count(2))->create();
        MenuVariation::factory()->count(100)->has(MenuVariationValue::factory()->count(3))->create();
        MenuVariation::factory()->count(100)->has(MenuVariationValue::factory()->count(4))->create();
        MenuVariation::factory()->count(100)->has(MenuVariationValue::factory()->count(5))->create();
    }
}
