<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecialInstructionToRestaurantOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            $table->string('special_instruction')->nullable()->after('options');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            $table->dropColumn(['special_instruction']);
        });
    }
}
