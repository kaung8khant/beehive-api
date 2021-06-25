<?php

namespace App\Console\Commands;

use App\Helpers\OrderAssignHelper;
use Illuminate\Console\Command;

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
        $this->assignOrderToOther();
    }
}
