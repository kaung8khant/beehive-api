<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuVariant;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Menu::factory()->count(500)->has(MenuVariant::factory()->count(1))->create();
    }
}
