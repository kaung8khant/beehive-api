<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\RestaurantBranch;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

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
        $users = [
            [
                'slug' => $this->generateUniqueSlug(),
                'username' => 'admin',
                'name' => 'Admin',
                'phone_number' => '09123456789',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'username' => 'admin1',
                'name' => 'Admin',
                'phone_number' => '09123456788',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'shop_id' => Shop::factory()->create()->id,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'username' => 'admin2',
                'name' => 'Admin2',
                'phone_number' => '0912312333',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'restaurant_branch_id' => RestaurantBranch::factory()->create()->id,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'username' => 'driver',
                'name' => 'Driver',
                'phone_number' => '0912345689',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
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
