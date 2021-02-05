<?php

namespace Database\Seeders;

use App\Models\Township;
use Illuminate\Database\Seeder;

class TownshipSeeder extends Seeder
{
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
                "city_id" => 1
            ],
            [
                "name" => "Sule",
                "city_id" => 1
            ],
            [
                "name" => "Kyauktada",
                "city_id" => 1
            ],
            [
                "name" => "Pyin Oo Lwin",
                "city_id" => 2
            ],
        ];

        Township::insert($townships);
    }
}
