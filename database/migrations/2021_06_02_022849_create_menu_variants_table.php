<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id');
            $table->string('slug')->unique();
            $table->json('variant');
            $table->decimal('price', 12, 0);
            $table->decimal('tax', 12, 2);
            $table->decimal('discount', 12, 2);
            $table->boolean('is_enable')->default(1);
            $table->timestamps();
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_variants');
    }
}
