<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Admin',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shop',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Restaurant',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Driver',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Collector',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Logistics',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
