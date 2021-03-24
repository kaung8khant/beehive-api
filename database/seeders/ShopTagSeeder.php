<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\ShopTag;


class ShopTagSeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            [
                'name' => 'Handbag',
                'slug' => $this->generateUniqueSlug(),
            ],
            [
                'name' => 'Plate',
                'slug' => $this->generateUniqueSlug(),
            ],
        ];

        foreach ($tags as $tag) {
            ShopTag::create($tag);
        }
    }
}
