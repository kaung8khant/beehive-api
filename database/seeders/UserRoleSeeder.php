<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        $user = User::find(4);
        $roleId = Role::find(4)->id;
        $user->roles()->attach($roleId);

        $user = User::find(5);
        $roleId = Role::find(5)->id;
        $user->roles()->attach($roleId);

        $user = User::find(6);
        $roleId = Role::find(6)->id;
        $user->roles()->attach($roleId);
    }
}
