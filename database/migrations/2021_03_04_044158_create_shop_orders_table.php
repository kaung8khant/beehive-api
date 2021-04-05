<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->date('order_date');
            $table->string('special_instruction')->nullable();
            $table->enum('payment_mode', ['COD', 'CBPay', 'KPay', 'MABPay']);
            $table->enum('delivery_mode', ['pickup', 'delivery']);
            $table->unsignedBigInteger('promocode_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('order_status')->default('pending');
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('promocode_id')->references('id')->on('promocodes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_orders');
    }
}
