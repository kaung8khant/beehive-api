<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->dropForeign('shops_township_id_foreign');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedBigInteger('township_id')->nullable()->change();
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('address')->nullable(false)->change();
            $table->dropForeign('shops_township_id_foreign');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedBigInteger('township_id')->nullable(false)->change();
            $table->foreign('township_id')->references('id')->on('townships')->onDelete('cascade');
        });
    }
}
