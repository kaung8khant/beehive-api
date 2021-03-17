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
            $table->string('label');
            $table->string('house_number');
            $table->string('floor')->nullable();
            $table->string('street_name');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->boolean('is_primary')->default(0);
            $table->unsignedBigInteger('township_id');
            $table->unsignedBigInteger('customer_id');
            $table->unique(['label', 'customer_id']);
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
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