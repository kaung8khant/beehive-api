<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_branches', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string('name_mm')->unique()->nullable();
            $table->string("slug")->unique();
            $table->boolean("enable");
            $table->string("address");
            $table->string("contact_number");
            $table->time("opening_time");
            $table->time("closing_time");
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamps();
            $table->unsignedBigInteger('township_id');
            $table->unsignedBigInteger('shop_id');
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shop_branches');
    }
}
