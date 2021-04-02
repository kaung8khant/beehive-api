<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\MenuTopping;
use Illuminate\Database\Seeder;

class MenuToppingSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MenuTopping::factory()->count(500)->create();
    }
}
