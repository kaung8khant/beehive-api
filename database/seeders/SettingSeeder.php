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
                'key' => 'restaurant_search_radius',
                'value' => '10',
                'data_type' => 'integer',
                'group_name' => 'general',
            ],
            [
                'key' => 'commercial',
                'value' => '5',
                'data_type' => 'integer',
                'group_name' => 'general',
            ],
            [
                'key' => 'currency',
                'value' => 'MMK',
                'data_type' => 'string',
                'group_name' => 'general',
            ],
            [
                'key' => 'printer',
                'value' => 'A4',
                'data_type' => 'string',
                'group_name' => 'general',
            ],
            [
                'key' => 'phone_number',
                'value' => '0977777777',
                'data_type' => 'string',
                'group_name' => 'contact',
            ],
            [
                'key' => 'email',
                'value' => 'beehive@gmail.com',
                'data_type' => 'string',
                'group_name' => 'contact',
            ],
            [
                'key' => 'facebook',
                'value' => 'beehive@facebook.com',
                'data_type' => 'string',
                'group_name' => 'contact',
            ],
            [
                'key' => 'website',
                'value' => 'www.beehivemm.com',
                'data_type' => 'string',
                'group_name' => 'contact',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
