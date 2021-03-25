<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Promocode;
use Illuminate\Database\Seeder;

class PromocodeSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $promocode_list = [
            [
                "slug" => $this->generateUniqueSlug(),
                "code" => "STARTTHINGYAN",
                "type" => "fix",
                "usage" => "shop",
                "amount" => "2000",
                "description" => "Start Thingyan discount",

            ],
            [
                "slug" => $this->generateUniqueSlug(),
                "code" => "THINGYAN",
                "type" => "percentage",
                "usage" => "shop",
                "amount" => "10",
                "description" => "Thingyan discount",

            ],
            [
                "slug" => $this->generateUniqueSlug(),
                "code" => "NEWUSER",
                "type" => "percentage",
                "usage" => "shop",
                "amount" => "10",
                "description" => "Matching discount",

            ],
            [
                "slug" => $this->generateUniqueSlug(),
                "code" => "NEWUSER",
                "type" => "percentage",
                "usage" => "restaurant",
                "amount" => "10",
                "description" => "Matching discount",

            ],
            [
                "slug" => $this->generateUniqueSlug(),
                "code" => "HPBD",
                "type" => "percentage",
                "usage" => "restaurant",
                "amount" => "10",
                "description" => "Matching discount",

            ],
            [
                "slug" => $this->generateUniqueSlug(),
                "code" => "HPBD",
                "type" => "percentage",
                "usage" => "shop",
                "amount" => "10",
                "description" => "Matching discount",

            ],
            [ //7
                "slug" => $this->generateUniqueSlug(),
                "code" => "THINGYAN",
                "type" => "percentage",
                "usage" => "shop",
                "amount" => "10",
                "description" => "Thingyan discount",

            ],

        ];

        foreach ($promocode_list as $promocode) {
            Promocode::create($promocode);
        }
    }
}
