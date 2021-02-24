<?php

namespace Database\Seeders;

use App\Models\RestaurantBranch;
use Illuminate\Database\Seeder;

class RestaurantBranchMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $restaurantBranch = RestaurantBranch::find(1);
        $menuIdArrays = [1,2];
        foreach ($menuIdArrays as $id) {
            $restaurantBranch->menus()->attach($id);
        }
    }
}
