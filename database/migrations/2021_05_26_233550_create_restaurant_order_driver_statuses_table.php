<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantOrderDriverStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_order_driver_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_order_driver_id');
            $table->string('status');
            $table->timestamps();
            $table->foreign('restaurant_order_driver_id', "re_order_driver_id_foreign")->references('id')->on('restaurant_order_drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_order_driver_statuses');
    }
}
