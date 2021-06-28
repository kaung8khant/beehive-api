<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNotifyNumbersToShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->json('notify_numbers')->nullable()->after('contact_number');
        });

        DB::statement("ALTER TABLE shops MODIFY `city` VARCHAR(255) AFTER `address`");
        DB::statement("ALTER TABLE shops MODIFY `township` VARCHAR(255) AFTER `city`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['notify_numbers']);
        });
    }
}
