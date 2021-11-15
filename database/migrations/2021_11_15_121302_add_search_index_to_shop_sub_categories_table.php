<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSearchIndexToShopSubCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_sub_categories', function (Blueprint $table) {
            $table->dropForeign('shop_sub_categories_shop_category_id_foreign');
        });

        DB::statement("ALTER TABLE `shop_sub_categories` MODIFY `shop_category_id` BIGINT UNSIGNED NOT NULL AFTER `id`");

        Schema::table('shop_sub_categories', function (Blueprint $table) {
            $table->integer('search_index')->default(0)->after('shop_category_id');
            $table->foreign('shop_category_id')->references('id')->on('shop_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_sub_categories', function (Blueprint $table) {
            $table->dropColumn(['search_index']);
        });
    }
}
