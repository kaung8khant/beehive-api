<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\ShopBranch;
use Illuminate\Database\Seeder;

class ShopBranchSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $shopBranches = [
            [
                'name' => 'Branch1',
                'name_mm' => 'ဆိုင်ခွဲ(၁)',
                'slug' => $this->generateUniqueSlug(),
                'address' => 'NO(88), Kannar Road, Latha T/S, Yangon',
                'contact_number' => '095172935',
                'opening_time' => '08:00',
                'closing_time' => '20:00',
                'latitude' => 16.7778,
                'longitude' => 96.1514,
                'township_id' => 1,
                'shop_id' => 1,
                'is_enable' => true,
            ],
            [
                'name' => 'Branch2',
                'name_mm' => 'ဆိုင်ခွဲ(၂)',
                'slug' => $this->generateUniqueSlug(),
                'address' => 'NO(88), Kannar Road, Latha T/S, Yangon',
                'contact_number' => '095172935',
                'opening_time' => '08:00',
                'closing_time' => '20:00',
                'latitude' => 16.7778,
                'longitude' => 96.1514,
                'township_id' => 1,
                'shop_id' => 1,
                'is_enable' => true,
            ],
            [
                'name' => 'Branch3',
                'name_mm' => 'ဆိုင်ခွဲ(၃)',
                'slug' => $this->generateUniqueSlug(),
                'address' => 'NO(88), Kannar Road, Kyauktada T/S, Yangon',
                'contact_number' => '095172935',
                'opening_time' => '08:00',
                'closing_time' => '20:00',
                'latitude' => 16.7778,
                'longitude' => 96.1514,
                'township_id' => 2,
                'shop_id' => 2,
                'is_enable' => true,
            ],
            [
                'name' => 'Pyin Oo Lwin',
                'name_mm' => 'ဆိုင်ခွဲ(၄)',
                'slug' => $this->generateUniqueSlug(),
                'address' => 'NO(88), Kannar Road, Kyauktada T/S, Yangon',
                'contact_number' => '095172935',
                'opening_time' => '08:00',
                'closing_time' => '20:00',
                'latitude' => 16.7778,
                'longitude' => 96.1514,
                'township_id' => 2,
                'shop_id' => 2,
                'is_enable' => true,
            ],
        ];

        foreach ($shopBranches as $shopBranch) {
            ShopBranch::create($shopBranch);
        }
    }
}
