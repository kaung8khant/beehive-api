<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->date('order_date');
            $table->string('special_instruction')->nullable();
            $table->enum('payment_mode', ['COD', 'CBPay', 'KPay', 'MABPay']);
            $table->enum('delivery_mode', ['package', 'delivery']);
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_orders');
    }
}
