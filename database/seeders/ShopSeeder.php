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
                "name" => "Impact Myanmar",
                "name_mm" => "Impact Myanmar",
                "official" => true ,
                "enable" => true ,
                "slug" => $this->generateUniqueSlug(),
            ],
            [
                "name" => "Marigold",
                "name_mm" => "Marigold",
                "official" => true ,
                "enable" => true ,
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        foreach ($shops as $shop) {
            Shop::create($shop);
        }
    }
}
