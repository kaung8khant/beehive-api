<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopOrderDriverStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_order_driver_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_order_driver_id');
            $table->string('status');
            $table->timestamps();
            $table->foreign('shop_order_driver_id')->references('id')->on('shop_order_drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_order_driver_statuses');
    }
}
