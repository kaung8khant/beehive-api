<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RestaurantBranch;
use App\Models\Menu;

class RestaurantBranchMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $branches = RestaurantBranch::all();

        foreach ($branches as $b) {
            $menus = Menu::where('restaurant_id', $b->restaurant_id)->pluck('id');
            $b->availableMenus()->attach($menus);
        }
    }
}
