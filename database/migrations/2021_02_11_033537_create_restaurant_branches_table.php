<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_branches', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string('name_mm')->unique()->nullable();
            $table->string("slug")->unique();
            $table->boolean("enable");
            $table->string("address");
            $table->string("contact_number");
            $table->time("opening_time");
            $table->time("closing_time");
            $table->double('latitude');
            $table->double('longitude');
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->unsignedBigInteger('township_id');
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurant_branches');
    }
}
