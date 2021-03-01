<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\Role;

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
                'name' => 'Driver',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Collector',
            ],
        ];
        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
