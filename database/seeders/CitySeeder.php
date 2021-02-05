<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;

class CitySeeder extends Seeder
{
    use StirngHelper;
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
