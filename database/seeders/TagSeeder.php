<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
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
                "name" => "Italian Food",
                "name_mm" => "အီတလီအစားအစာ",
                "slug" => $this->generateUniqueSlug(),
            ],
            [
                "name" => "Chenese Food",
                "name_mm" => "တရုတ်အစားအစာ",
                "slug" => $this->generateUniqueSlug(),
            ],
        ];
        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
