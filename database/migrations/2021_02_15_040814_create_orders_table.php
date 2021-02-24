<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->date('order_date');
            $table->string('special_instruction')->nullable();
            $table->enum('order_type', ['restaurant', 'shop']);
            $table->enum('payment_mode', ['COD', 'CBPay', 'KPay', 'MABPay']);
            $table->enum('delivery_mode', ['package', 'delivery']);
            $table->enum('rating', [1, 2, 3, 4, 5])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
