<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orders = [
            [
                'slug' => $this->generateUniqueSlug(),
                'order_date' => '2021-2-16',
                'special_instruction' => 'Testing',
                'order_type' => 'restaurant',
                'payment_mode' => 'KPay',
                'delivery_mode' => 'package',
                'rating_status' => 'two',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'order_date' => '2021-2-15',
                'special_instruction' => 'Testing',
                'order_type' => 'shop',
                'payment_mode' => 'COD',
                'delivery_mode' => 'delivery',
                'rating_status' => 'three',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'order_date' => '2021-2-17',
                'special_instruction' => 'Testing',
                'order_type' => 'shop',
                'payment_mode' => 'CBPay',
                'delivery_mode' => 'delivery',
                'rating_status' => 'three',
            ],
        ];
        foreach ($orders as $order) {
            Order::create($order);
        }
    }
}
