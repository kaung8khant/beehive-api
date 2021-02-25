<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('item_id');
            $table->string('item_name');
            $table->enum('item_type', ['product', 'menu']);
            $table->integer('quantity');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax', 12, 2);
            $table->decimal('discount', 12, 2);
            $table->boolean('is_deleted')->default(0);
            $table->timestamps();
            $table->unique(['order_id', 'item_id']);
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
