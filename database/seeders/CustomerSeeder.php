<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Helpers\StringHelper;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Customer::create([
            'slug' => $this->generateUniqueSlug(),
            'username' => 'customer1',
            'email' => 'customer1@example.com',
            'name' => 'Test Customer',
            'phone_number' => '09123456789',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'gender' => 'male',
            'date_of_birth' => '1993-11-16',
        ]);
    }
}
