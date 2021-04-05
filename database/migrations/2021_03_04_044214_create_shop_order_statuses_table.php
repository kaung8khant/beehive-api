<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'preparing', 'pickUp', 'onRoute', 'delivered', 'cancelled'])->default('pending');
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('shop_order_item_id');
            $table->timestamps();
            $table->foreign('shop_order_item_id')->references('id')->on('shop_order_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_order_statuses');
    }
}
