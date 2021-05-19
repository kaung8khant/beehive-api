<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDataTypeToPromocodeRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promocode_rules', function (Blueprint $table) {
            $table->string('data_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promocode_rules', function (Blueprint $table) {
            $table->enum('data_type', ['before_date', 'after_date', 'exact_date', 'total_usage', 'per_user_usage', 'matching'])->change();
        });
    }
}
