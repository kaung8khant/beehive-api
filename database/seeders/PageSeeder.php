<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    use StringHelper;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pages = [
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'User Privacy Policy',
                'content' => '### User Privacy Policy'
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Driver Privacy Policy',
                'content' => '### Driver Privacy Policy'
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Terms & Conditions',
                'content' => '### Terms & Conditions'
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'About Us',
                'content' => '### About Us'
            ],
        ];
        foreach ($pages as $page) {
            Page::create($page);
        }
    }
}
