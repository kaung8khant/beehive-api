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
                "name_mm" => "ရန်ကုန်",
                "slug" => $this->generateUniqueSlug(),
            ],
            [
                "name" => "Mandalay",
                "name_mm" => "မန္တလေး",
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        foreach ($cities as $city) {
            City::create($city);
        }
    }
}