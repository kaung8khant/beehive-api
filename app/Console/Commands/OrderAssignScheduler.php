<?php

namespace App\Console\Commands;

use App\Helpers\OrderAssignHelper;
use App\Helpers\StringHelper;
use App\Jobs\AssignOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OrderAssignScheduler extends Command
{
    use OrderAssignHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:assign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Order assgin scheduler for other driver.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $restaurantOrders = DB::table('restaurant_orders')
        //     ->select('id', 'slug', 'order_date')
        //     ->where('order_type', 'schedule')
        //     ->where('order_status', 'pending')
        //     ->get();

        // $ordersToAssign = $restaurantOrders->map(function ($order) {
        //     $driverOrder = DB::table('restaurant_order_drivers')
        //         ->where('restaurant_order_id', $order->id)
        //         ->first();

        //     if (!$driverOrder && Carbon::now()->addHour()->gt(Carbon::parse($order->order_date))) {
        //         return $order;
        //     }
        // })->filter()->values();

        // foreach ($ordersToAssign as $order) {
        //     $uniqueKey = StringHelper::generateUniqueSlug();
        //     AssignOrder::dispatch($uniqueKey, $order->slug, 'restaurant');
        // }

        //$this->assignOrderToOther();
    }
}
