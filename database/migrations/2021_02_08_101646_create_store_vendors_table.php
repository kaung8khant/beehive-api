<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_vendors', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string('name_mm')->unique()->nullable();
            $table->string("slug")->unique();
            $table->string("address");
            $table->string("contactNumber");
            $table->time("openingTime");
            $table->time("closingTime");
            $table->boolean("enable");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_vendors');
    }
}
