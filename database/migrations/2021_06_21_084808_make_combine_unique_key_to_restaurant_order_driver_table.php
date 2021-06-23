<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCombineUniqueKeyToRestaurantOrderDriverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_order_drivers', function (Blueprint $table) {
            $table->unique(["user_id", "restaurant_order_id"], 'driver_restaurant_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_order_drivers', function (Blueprint $table) {
            $table->dropUnique('driver_restaurant_unique');
        });
    }
}
