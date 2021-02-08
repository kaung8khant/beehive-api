<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Township;
use Illuminate\Database\Seeder;

class TownshipSeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $townships = [
            [
                "name" => "Latha",
                "slug" => $this->generateUniqueSlug(),
                "city_id" => 1
            ],
            [
                "name" => "Sule",
                "slug" => $this->generateUniqueSlug(),
                "city_id" => 1
            ],
            [
                "name" => "Kyauktada",
                "slug" => $this->generateUniqueSlug(),
                "city_id" => 1
            ],
            [
                "name" => "Pyin Oo Lwin",
                "slug" => $this->generateUniqueSlug(),
                "city_id" => 2
            ],
        ];

        foreach ($townships as $township) {
            Township::create($township);
        }
    }
}
