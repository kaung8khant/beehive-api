<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuOptionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_option_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_option_id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('price', 12, 0);
            $table->timestamps();
            $table->foreign('menu_option_id')->references('id')->on('menu_options')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_option_items');
    }
}
