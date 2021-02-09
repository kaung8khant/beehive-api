<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagRestaurantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tag_restaurant', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('restaurant_tag_id');
            $table->primary(['restaurant_id', 'restaurant_tag_id']);
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('restaurant_tag_id')->references('id')->on('restaurant_tags')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tag_restaurant');
    }
}
