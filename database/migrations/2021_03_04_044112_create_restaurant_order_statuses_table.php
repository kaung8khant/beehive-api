<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'preparing', 'pickUp', 'onRoute', 'delivered', 'cancelled'])->default('pending');
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('restaurant_order_id');
            $table->timestamps();
            $table->foreign('restaurant_order_id')->references('id')->on('restaurant_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_order_statuses');
    }
}
