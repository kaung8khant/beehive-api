<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromocodeRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promocode_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('value');
            $table->enum('data_type', ['happy hour', 'date period','birthday','user limit']);
            $table->enum('operator', ['equal', 'less than','greater than','less than equal','greater than equal']);
            $table->unsignedBigInteger('promocode_id');
            $table->timestamps();
            $table->foreign('promocode_id')->references('id')->on('promocodes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promocode_rules');
    }
}
