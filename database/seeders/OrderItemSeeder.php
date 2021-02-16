<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\OrderItem;

class OrderItemSeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $orderItems = [
            [
                "item_id" => "IT0002",
                "item_name" => "Chicken Pizza",
                "item_type" => "menu",
                "amount"=>6000,
                "quantity"=>2,
                "tax"=>5,
                "discount"=>200,
                "is_deleted" => false,
                "order_id" => 1
            ],
            [
                "item_id" => "IT0003",
                "item_name" => "Seafood Pizza",
                "item_type" => "menu",
                "amount"=>8000,
                "quantity"=>2,
                "tax"=>5,
                "discount"=>200,
                "is_deleted" => false,
                "order_id" => 1
            ],

        ];
        foreach ($orderItems as $orderItem) {
            OrderItem::create($orderItem);
        }
    }
}
