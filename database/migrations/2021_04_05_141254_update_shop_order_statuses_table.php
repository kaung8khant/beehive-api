<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShopOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('shop_order_statuses', function (Blueprint $table) {
            $table->dropForeign(['shop_order_id']);
            $table->dropColumn('shop_order_id');
            $table->unsignedBigInteger('shop_order_item_id')->after('created_by');;
            $table->foreign('shop_order_item_id')->references('id')->on('shop_order_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
