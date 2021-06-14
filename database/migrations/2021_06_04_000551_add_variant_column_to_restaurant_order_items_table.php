<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVariantColumnToRestaurantOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            $table->json('variant')->nullable()->after('variations');
            $table->json('variations')->nullable()->change();
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
            $table->dropColumn(['variant']);
        });
    }
}
