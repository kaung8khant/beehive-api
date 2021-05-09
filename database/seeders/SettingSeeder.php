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
                'group_name' => 'business'
            ],
            [
                'key' => 'Default Commercial Tax (%)',
                'value' => '5',
                'data_type' => 'integer',
                'group_name' => 'business'
            ],
            [
                'key' => 'Default Currency',
                'value' => 'MMK',
                'data_type' => 'string',
                'group_name' => 'business'
            ],
            [
                'key' => 'Printer Format',
                'value' => 'A4',
                'data_type' => 'string',
                'group_name' => 'printer'
            ],
            [
                'key' => 'Phone Number',
                'value' => '0977777777',
                'data_type' => 'string',
                'group_name' => 'contact'
            ],
            [
                'key' => 'Email',
                'value' => 'beehive@gmail.com',
                'data_type' => 'string',
                'group_name' => 'contact'
            ],
            [
                'key' => 'Facebook',
                'value' => 'beehive@facebook.com',
                'data_type' => 'string',
                'group_name' => 'contact'
            ],
            [
                'key' => 'Website',
                'value' => 'www.beehivemm.com',
                'data_type' => 'string',
                'group_name' => 'contact'
            ],
        ];
        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
