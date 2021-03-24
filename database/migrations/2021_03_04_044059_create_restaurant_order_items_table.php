<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('menu_name');
            $table->integer('quantity');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax', 12, 2);
            $table->decimal('discount', 12, 2);
            $table->json('variations');
            $table->json('toppings');
            $table->boolean('is_deleted')->default(0);
            $table->unsignedBigInteger('restaurant_order_id');
            $table->unsignedBigInteger('menu_id')->nullable();
            $table->timestamps();
            $table->unique(['restaurant_order_id', 'menu_id']);
            $table->foreign('restaurant_order_id')->references('id')->on('restaurant_orders')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_order_items');
    }
}
