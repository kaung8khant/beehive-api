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
        $restaurants = RestaurantBranch::all();

        foreach ($restaurants as $restaurant) {
            $menus = Menu::where('restaurant_id', $restaurant->id)->pluck('id');
            $restaurant->availableMenus()->attach($menus);
        }
    }
}
