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
                'key' => 'Restaurant Filter Range',
                'value' => '10',
                'data_type' => 'integer',
            ],
            [
                'key' => 'Default Commercial Tax (%)',
                'value' => '5',
                'data_type' => 'integer',
            ],
            [
                'key' => 'Default Currency',
                'value' => 'MMK',
                'data_type' => 'string',
            ],  [
                'key' => 'Printer Format',
                'value' => 'A4',
                'data_type' => 'string',
            ],
        ];
        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
