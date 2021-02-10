<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->string('name_mm')->unique();
            $table->string('price');
            $table->string('description');
            $table->string('description_mm');
            // $table->unsignedBigInteger('restaurantCategory_id');
            // $table->foreign('restaurantCategory_id')->references('id')->on('restaurantCategories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
