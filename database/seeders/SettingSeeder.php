<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

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
                'data_type' => 'integer'
            ],
            [
                'key' => 'commission',
                'value' => '10',
                'data_type' => 'integer'
            ],
        ];
        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
