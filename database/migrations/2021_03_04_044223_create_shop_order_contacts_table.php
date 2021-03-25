<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopOrderContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_order_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_order_id');
            $table->string('customer_name');
            $table->string('phone_number');
            $table->string('house_number');
            $table->string('floor')->nullable();
            $table->string('street_name');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->unsignedBigInteger('township_id');
            $table->timestamps();
            $table->foreign('shop_order_id')->references('id')->on('shop_orders')->onDelete('cascade');
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_order_contacts');
    }
}
