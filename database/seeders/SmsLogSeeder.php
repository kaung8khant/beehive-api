<?php

namespace Database\Seeders;

use App\Models\SmsLog;
use Illuminate\Database\Seeder;

class SmsLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SmsLog::factory()->count(50)->create();
    }
}
