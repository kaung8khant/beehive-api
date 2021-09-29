<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreOrderToRestaurantBranches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->boolean('pre_order')->default(0)->after('free_delivery');
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
            $table->dropColumn(['pre_order']);
        });
    }
}
