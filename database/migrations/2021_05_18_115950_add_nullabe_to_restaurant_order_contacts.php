<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNullabeToRestaurantOrderContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_order_contacts', function (Blueprint $table) {
            $table->string('house_number')->nullable()->change();
            $table->string('street_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_order_contacts', function (Blueprint $table) {
            $table->string('house_number')->nullable(false)->change();
            $table->string('street_name')->nullable(false)->change();
        });
    }
}
