<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDatabaseJunkImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:database-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete unused junk images from the database';

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
        $images = DB::table('files')->pluck('file_name', 'id');

        foreach ($images as $key => $value) {
            if (!Storage::exists('images/large/' . $value)) {
                DB::table('files')->where('id', $key)->delete();
            }
        }

        return 0;
    }
}
