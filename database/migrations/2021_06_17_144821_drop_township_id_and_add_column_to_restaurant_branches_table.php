<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTownshipIdAndAddColumnToRestaurantBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->dropForeign(['township_id']);
            $table->dropColumn('township_id');
            $table->string('city')->nullable();
            $table->string('township')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->unsignedBigInteger('township_id');
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
            $table->dropColumn(['township','city']);
        });
    }
}
