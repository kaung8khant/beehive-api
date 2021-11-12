<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\OneSignalHelper;
use App\Helpers\StringHelper;
use App\Models\ShopOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopOrderReminder extends Command
{
    use OneSignalHelper, StringHelper;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $orders = ShopOrder::with('drivers', 'drivers.status')
            ->where('order_status', '!=', 'cancelled')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(2)->startOfDay())
            ->whereHas('drivers.status', function ($q) {
                $q->where('status', 'rejected');
                $q->orWhere('status', 'pending');
            })->orDoesntHave('drivers')
            ->orderByDesc('id')
            ->get();
        if (count($orders) > 0) {
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'Admin');
            })->pluck('slug');

            $request = new Request();
            $request['slugs'] = $admins;
            $request['message'] = 'You have ' . count($orders) . ' unassigned shop order!';
            $request['data'] = ["type" => "noti", "title" =>  "Some shop order are not assigned yet!"];

            $appId = config('one-signal.admin_app_id');
            $fields = OneSignalHelper::prepareNotification($request, $appId);
            $uniqueKey = StringHelper::generateUniqueSlug();

            $response = OneSignalHelper::sendPush($fields, 'admin');
        }
    }
}
