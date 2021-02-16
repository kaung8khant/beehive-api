<?php

namespace Database\Seeders;

use App\Models\Rating;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 'receiver_id', 'receiver_type', 'rating', 'review', 'order_id', 'customer_id'
        $ratings = [
            [
                'receiver_id' => 1,
                'receiver_type' => 'restaurant',
                'rating' => 2,
                'review' => 'Lorem Ipsum copy in various charsets and languages for layouts',
                'order_id' => 1,
                'customer_id' => 1,
            ],
            [
                'receiver_id' => 2,
                'receiver_type' => 'shop',
                'rating' => 3,
                'review' => 'Lorem Ipsum copy in various charsets and languages for layouts',
                'order_id' => 1,
                'customer_id' => 1,
            ],
            [
                'receiver_id' => 3,
                'receiver_type' => 'biker',
                'rating' => 3,
                'review' => 'Lorem Ipsum copy in various charsets and languages for layouts',
                'order_id' => 1,
                'customer_id' => 1,
            ],

        ];
        foreach ($ratings as $rating) {
            Rating::create($rating);
        }
    }
}
