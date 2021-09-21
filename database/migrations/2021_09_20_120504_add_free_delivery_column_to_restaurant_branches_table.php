<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreeDeliveryColumnToRestaurantBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->boolean('free_delivery')->nullable()->default(1)->after('is_enable');
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
            $table->dropColumn(['free_delivery']);
        });
    }
}
