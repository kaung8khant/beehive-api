<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DeleteStorageJunkImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:storage-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete unused junk images from the storage';

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
        $imageSizes = array_keys(config('images'));
        $files = Storage::files('images/large');

        foreach ($files as $file) {
            $fileName = explode('/', $file);
            $image = DB::table('files')->where('file_name', $fileName[2])->first();

            if (!$image) {
                foreach ($imageSizes as $size) {
                    Storage::delete('images/' . $size . '/' . $fileName[2]);
                }
            }
        }

        return 0;
    }
}
