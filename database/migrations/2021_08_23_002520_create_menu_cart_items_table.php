<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuCartItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_cart_id');
            $table->unsignedBigInteger('menu_id');
            $table->json('menu');
            $table->timestamps();
            $table->foreign('menu_cart_id')->references('id')->on('menu_carts')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_cart_items');
    }
}
