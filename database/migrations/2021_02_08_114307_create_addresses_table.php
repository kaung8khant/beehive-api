<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->integer('house_number');
            $table->integer('floor');
            $table->string('street_name');
            $table->double('latitude');
            $table->double('longitude');
            // $table->unsignedBigInteger('township_id');
            // $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
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
        Schema::dropIfExists('addresses');
    }
}