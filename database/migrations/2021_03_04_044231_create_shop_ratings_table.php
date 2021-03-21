<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_ratings', function (Blueprint $table) {
            $table->id();
            $table->integer('target_id');
            $table->enum('target_type', ['shop', 'customer', 'biker','product']);
            $table->integer('source_id');
            $table->enum('source_type', ['shop', 'customer', 'biker','product']);
            $table->integer('rating');
            $table->text('review')->nullable();
            $table->unsignedBigInteger('shop_order_id');
            $table->timestamps();
            $table->foreign('shop_order_id')->references('id')->on('shop_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_ratings');
    }
}
