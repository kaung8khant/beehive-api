<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalculateDriverSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:driver-session';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate driver session';

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
        echo 'OK';
    }
}
