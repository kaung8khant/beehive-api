<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHouseNumberAndFloorAndStreetNameToAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('floor');
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->integer('floor')->nullable();
            $table->string('street_name')->nullable()->change();
            $table->string('house_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('floor');
        });
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('floor')->nullable(false);
            $table->string('street_name')->nullable(false)->change();
            $table->string('house_number')->nullable(false)->change();
        });
    }
}
