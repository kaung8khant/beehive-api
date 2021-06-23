<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCombineUniqueKeyToShopOrderDriverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_order_drivers', function (Blueprint $table) {
            $table->unique(["user_id", "shop_order_id"], 'driver_shop_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_order_drivers', function (Blueprint $table) {
            $table->dropUnique('driver_shop_unique');
        });
    }
}
