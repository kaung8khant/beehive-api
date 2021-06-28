<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNotifyNumbersToRestaurantBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->json('notify_numbers')->nullable()->after('contact_number');
        });

        DB::statement("ALTER TABLE restaurant_branches MODIFY `city` VARCHAR(255) AFTER `address`");
        DB::statement("ALTER TABLE restaurant_branches MODIFY `township` VARCHAR(255) AFTER `city`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_branches', function (Blueprint $table) {
            $table->dropColumn(['notify_numbers']);
        });
    }
}
