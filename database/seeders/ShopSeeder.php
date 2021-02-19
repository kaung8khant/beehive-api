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
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Marigold',
                'name_mm' => 'Marigold',
                'is_official' => true,
                'is_enable' => true,
            ],
        ];
        foreach ($shops as $shop) {
            Shop::create($shop);
        }
    }
}
