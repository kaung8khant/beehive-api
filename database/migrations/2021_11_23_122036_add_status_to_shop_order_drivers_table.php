<?php

use App\Models\ShopOrderDriver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToShopOrderDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_order_drivers', function (Blueprint $table) {
            $table->string('status')->after('user_id')->nullable();
        });

        $orderDrivers = ShopOrderDriver::with(['status' => function ($query) {
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
        Schema::table('shop_order_drivers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
