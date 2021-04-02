<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\PromocodeRule;
use Illuminate\Database\Seeder;

class PromocodeRuleSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $promocoderule_list = [
            [
                "value" => "2021-04-13",
                "data_type" => "exact_date",
                "promocode_id" => 1,

            ],
            [
                "value" => "2021-04-13",
                "data_type" => "before_date",
                "promocode_id" => 2,
            ],
            [
                "value" => "2021-03-23",
                "data_type" => "after_date",
                "promocode_id" => 2,

            ],
            [
                "value" => "new_customer",
                "data_type" => "matching",
                "promocode_id" => 3,

            ],
            [
                "value" => "new_customer",
                "data_type" => "matching",
                "promocode_id" => 4,

            ],
            [
                "value" => "dob",
                "data_type" => "matching",
                "promocode_id" => 5,
            ],
            [
                "value" => "dob",
                "data_type" => "matching",
                "promocode_id" => 6,
            ],
            [
                "value" => "2021-04-13",
                "data_type" => "before_date",
                "promocode_id" => 7,
            ],
            [
                "value" => "2021-03-23",
                "data_type" => "after_date",
                "promocode_id" => 7,

            ],

        ];

        foreach ($promocoderule_list as $promocode) {
            PromocodeRule::create($promocode);
        }
    }
}
