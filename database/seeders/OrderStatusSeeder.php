<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orderStatus = [
            [
                'created_by' => 'Ma Ma',
                'status' => 'pending',
                'order_id' => 1,
            ],
            [
                'created_by' => 'Su Su',
                'status' => 'preparing',
                'order_id' => 2,
            ],
            [
                'created_by' => 'Mg Mg',
                'status' => 'preparing',
                'order_id' => 3,
            ],

        ];
        foreach ($orderStatus as $status) {
            OrderStatus::create($status);
        }
    }
}
