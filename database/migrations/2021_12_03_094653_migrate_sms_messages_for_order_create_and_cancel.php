<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateSmsMessagesForOrderCreateAndCancel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $settings = [
            [
                'key' => 'vendor_restaurant_order_create',
                'value' => 'An order has been received.',
                'data_type' => 'string',
                'group_name' => 'sms',
            ],
            [
                'key' => 'vendor_shop_order_create',
                'value' => 'An order has been received.',
                'data_type' => 'string',
                'group_name' => 'sms',
            ],
            [
                'key' => 'customer_restaurant_order_create',
                'value' => 'Your order has successfully been created.',
                'data_type' => 'string',
                'group_name' => 'sms',
            ],
            [
                'key' => 'customer_restaurant_order_cancel',
                'value' => 'Your order has been cancelled.',
                'data_type' => 'string',
                'group_name' => 'sms',
            ],
            [
                'key' => 'customer_shop_order_create',
                'value' => 'Your order has successfully been created.',
                'data_type' => 'string',
                'group_name' => 'sms',
            ],
            [
                'key' => 'customer_shop_order_cancel',
                'value' => 'Your order has been cancelled.',
                'data_type' => 'string',
                'group_name' => 'sms',
            ],
        ];

        DB::table('settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
