<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\User;
use App\Models\Shop;
use App\Models\RestaurantBranch;

class UserSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $restaurantId = RestaurantBranch::find(1)->value('id');
        //$shopId = Shop::factory()->create()->id;

        //$customer->favoriteRestaurants()->attach($restaurantId);
        $users = [
            [
                'slug' => $this->generateUniqueSlug(),
                'username' => 'admin',
                'name' => 'Admin',
                'phone_number' => '09123456789',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'shop_id'=>  Shop::factory()->create()->id,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'username' => 'driver',
                'name' => 'Driver',
                'phone_number' => '0912345689',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                //'restaurant_branch_id' => $restaurantId,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'username' => 'collector',
                'name' => 'Collector',
                'phone_number' => '0912345789',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
        ];
        foreach ($users as $user) {
            User::create($user);
        }
    }
}
