<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shops = [
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Impact Myanmar',
                'name_mm' => 'Impact Myanmar',
                'is_official' => true,
                'is_enable' => true,
                'address' => 'NO(88), Kannar Road, Latha T/S, Yangon',
                'contact_number' => '095172935',
                'opening_time' => '08:00',
                'closing_time' => '20:00',
                'latitude' => 16.7778,
                'longitude' => 96.1514,
                'township_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Marigold',
                'name_mm' => 'Marigold',
                'is_official' => true,
                'is_enable' => true,
                'address' => 'NO(88), Kannar Road, Latha T/S, Yangon',
                'contact_number' => '095172935',
                'opening_time' => '08:00',
                'closing_time' => '20:00',
                'latitude' => 16.7778,
                'longitude' => 96.1514,
                'township_id' => 1,
            ],
        ];
        foreach ($shops as $shop) {
            Shop::create($shop);
        }
    }
}
