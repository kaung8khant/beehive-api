<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->integer('quantity');
            $table->decimal('amount', 12, 2);
            $table->decimal('tax', 12, 2);
            $table->decimal('discount', 12, 2);
            $table->json('variations');
            $table->json('shop');
            $table->boolean('is_deleted')->default(0);
            $table->timestamps();
            $table->unique(['shop_order_id', 'product_id']);
            $table->foreign('shop_order_id')->references('id')->on('shop_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_order_items');
    }
}
