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
        $restaurantBranchs = RestaurantBranch::all();

        foreach ($restaurantBranchs as $branch) {
            $menus = Menu::where('restaurant_id', $branch->restaurant_id)->pluck('id');
            $branch->availableMenus()->attach($menus);
        }
    }
}
