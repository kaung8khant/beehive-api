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
                "name" => "Yangon",
                "slug" => $this->generateUniqueSlug(),
            ],
            [
                "name" => "Mandalay",
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        City::insert($cities);
    }
}
