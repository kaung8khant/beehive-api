<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'key' => 'tax',
                'value' => '5',
                'data_type' => 'integer',
            ],
            [
                'key' => 'commission',
                'value' => '10',
                'data_type' => 'integer',
            ],
        ];
        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
