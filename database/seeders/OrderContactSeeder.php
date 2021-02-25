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
                'customer_name' => 'Aye Aye Hlaing',
                'phone_number' => '094789837832',
                'house_number' => 'NO(60)',
                'floor' => 5,
                'street_name' => 'Testing',
                'latitude' => 14.97934543,
                'longitude' => 30.34534,
                'order_id' => 1,
            ],
            [
                'customer_name' => 'Kyaw Kyaw',
                'phone_number' => '094789837832',
                'house_number' => 'NO(60)',
                'floor' => 5,
                'street_name' => 'Testing',
                'latitude' => 14.97934543,
                'longitude' => 30.34534,
                'order_id' => 2,
            ],
        ];

        foreach ($orderContacts as $orderContact) {
            OrderContact::create($orderContact);
        }
    }
}
