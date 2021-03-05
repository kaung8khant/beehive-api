<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->integer('target_id');
            $table->enum('target_type', ['restaurant', 'customer', 'biker']);
            $table->integer('source_id');
            $table->enum('source_type', ['restaurant', 'customer', 'biker']);
            $table->integer('rating');
            $table->text('review')->nullable();
            $table->timestamps();
            $table->foreign('restaurant_id')->references('id')->on('restaurant_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_ratings');
    }
}
