<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::find(1);
        $roleId = Role::find(1)->id;
        $user->roles()->attach($roleId);

        $user = User::find(2);
        $roleId = Role::find(2)->id;
        $user->roles()->attach($roleId);

        $user = User::find(3);
        $roleId = Role::find(3)->id;
        $user->roles()->attach($roleId);
    }
}
