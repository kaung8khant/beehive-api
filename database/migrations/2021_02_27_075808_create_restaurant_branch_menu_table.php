<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantBranchMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurant_branch_menu', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_branch_id');
            $table->unsignedBigInteger('menu_id');
            $table->primary(['restaurant_branch_id', 'menu_id']);
            $table->foreign('restaurant_branch_id')->references('id')->on('restaurant_branches')->onDelete('cascade');
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
        Schema::dropIfExists('restaurant_branch_menu');
    }
}
