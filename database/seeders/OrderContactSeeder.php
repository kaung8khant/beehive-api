<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\OrderContact;

class OrderContactSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orderContacts = [
            [
                "customerId" => "CUS0002",
                "customerName" => "Aye Aye Hlaing",
                "phoneNumber"=>"094789837832",
                "houseNumber"=>"NO(60)",
                "floor"=>5,
                "streetName"=>"Testing",
                "latitude" => 14.97934543,
                "longitude" => 30.34534,
                // "order_id" => 1
            ],
            [
                
                "customerId" => "CUS0003",
                "customerName" => "Kyaw Kyaw",
                "phoneNumber"=>"094789837832",
                "houseNumber"=>"NO(60)",
                "floor"=>5,
                "streetName"=>"Testing",
                "latitude" => 14.97934543,
                "longitude" => 30.34534,
                // "order_id" => 2
            ],
        ];
        foreach ($orderContacts as $orderContact) {
            OrderContact::create($orderContact);
        }
    }
}
