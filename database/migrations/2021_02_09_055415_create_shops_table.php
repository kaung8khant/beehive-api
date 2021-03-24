<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name')->unique();
            $table->string('address');
            $table->string('contact_number');
            $table->time('opening_time');
            $table->time('closing_time');
            $table->double('latitude');
            $table->double('longitude');
            $table->boolean('is_official')->default(0);
            $table->boolean('is_enable')->default(1);
            $table->unsignedBigInteger('township_id');
            $table->timestamps();
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
