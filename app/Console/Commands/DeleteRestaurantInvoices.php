<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteRestaurantInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:restaurant-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Restaurant PDF invoices after 3 months';

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
        $files = Storage::files('pdf/restaurants');

        foreach ($files as $file) {
            $filePath = explode('/', $file);
            $slug = explode('-', $filePath[2]);

            $updatedDate = DB::table('restaurant_orders')->where('slug', $slug)->value('updated_at');

            if (Carbon::parse($updatedDate)->lt(Carbon::now()->subMonths(3))) {
                Storage::delete('pdf/restaurants/' . $filePath[2]);
            }
        }
    }
}
