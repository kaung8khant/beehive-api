<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameMerchantReferenceInRestaurantOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->renameColumn('merchant_reference', 'payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->renameColumn('payment_reference', 'merchant_reference');
        });
    }
}
