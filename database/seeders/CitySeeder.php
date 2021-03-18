<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\City;

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
        $cities = [
            [
                'name' => 'Test',
                'slug' => $this->generateUniqueSlug(),
            ],
            [
                'name' => 'Test',
                'slug' => $this->generateUniqueSlug(),
            ],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
        // City::factory()->count(3)->has(Township::factory()->count(3))->create();
        // City::factory()->count(3)->has(Township::factory()->count(5))->create();
        // City::factory()->count(10)->create();
    }
}
