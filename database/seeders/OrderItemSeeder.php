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
                "itemId" => "IT0002",
                "itemName" => "Chicken Pizza",
                "itemType" => "menu",
                "amount"=>6000,
                "quantity"=>2,
                "tax"=>5,
                "discount"=>200,
                "isDeleted" => false,
                "order_id" => 1
            ],
            [
                "itemId" => "IT0003",
                "itemName" => "Seafood Pizza",
                "itemType" => "menu",
                "amount"=>8000,
                "quantity"=>2,
                "tax"=>5,
                "discount"=>200,
                "isDeleted" => false,
                "order_id" => 1
            ],
           
        ];
        foreach ($orderItems as $orderItem) {
            OrderItem::create($orderItem);
        }
    }
}
