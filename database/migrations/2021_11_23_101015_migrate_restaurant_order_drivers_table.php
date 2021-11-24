<?php

use App\Models\RestaurantOrderDriver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateRestaurantOrderDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_order_drivers', function (Blueprint $table) {
            $table->string('status')->after('user_id')->nullable();
        });

        $orderDrivers = RestaurantOrderDriver::with(['status' => function ($query) {
            $query->orderBy('id', 'desc');
        }])->get();
        foreach ($orderDrivers as $orderDriver) {
            $data = $orderDriver->toArray();
            $orderDriver['status'] = $data['status'] ? $data['status'][0]['status'] : null;
            $orderDriver->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_order_drivers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
