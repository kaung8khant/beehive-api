<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNullableInShopOrderContactsAndPromocode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_order_contacts', function (Blueprint $table) {
            $table->string('house_number')->nullable()->change();
            $table->string('street_name')->nullable()->change();
        });

        Schema::table('promocodes', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_order_contacts', function (Blueprint $table) {
            $table->string('house_number')->nullable(false)->change();
            $table->string('street_name')->nullable(false)->change();
        });

        Schema::table('promocodes', function (Blueprint $table) {
            $table->string('description')->nullable(false)->change();
        });
    }
}
