<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPromocodeAndAmountInShopOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->string('promocode')->nullable()->after('promocode_id');
            $table->decimal('promocode_amount', 12, 2)->nullable()->after('promocode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropColumn(['promocode', 'promocode_amount']);
        });
    }
}
