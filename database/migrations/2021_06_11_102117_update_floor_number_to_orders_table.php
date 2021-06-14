<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateFloorNumberToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_order_contacts', function (Blueprint $table) {
            $table->dropColumn(['floor']);
        });

        Schema::table('shop_order_contacts', function (Blueprint $table) {
            $table->integer('floor')->nullable()->after('house_number');
        });

        Schema::table('restaurant_order_contacts', function (Blueprint $table) {
            $table->dropColumn(['floor']);
        });

        Schema::table('restaurant_order_contacts', function (Blueprint $table) {
            $table->integer('floor')->nullable()->after('house_number');
        });

        DB::statement("ALTER TABLE addresses MODIFY `floor` INTEGER AFTER `house_number`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_order_contacts', function (Blueprint $table) {
            $table->dropColumn(['floor']);
        });

        Schema::table('shop_order_contacts', function (Blueprint $table) {
            $table->string('floor')->nullable()->after('house_number');
        });

        Schema::table('restaurant_order_contacts', function (Blueprint $table) {
            $table->dropColumn(['floor']);
        });

        Schema::table('restaurant_order_contacts', function (Blueprint $table) {
            $table->string('floor')->nullable()->after('house_number');
        });
    }
}
