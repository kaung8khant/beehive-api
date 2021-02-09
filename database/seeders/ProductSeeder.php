<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::create([
            'slug' => 'product',
            'name' => 'Shoes',
            'name_mm' => 'Shoes_mm',
            'description' => 'Description',
            'description_mm' => 'Description_mm',
            'proce' => 300,
        ]);
    }
}