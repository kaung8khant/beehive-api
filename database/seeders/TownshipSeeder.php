<?php

namespace Database\Seeders;

use App\Helpers\StringHelper;
use App\Models\Township;
use Illuminate\Database\Seeder;

class TownshipSeeder extends Seeder
{
    use StringHelper;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $townships = [
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myitkyina',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Bhamo',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mogaung',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mohnyin',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nanmar',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hopin',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpakan',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwegu',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sadong',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nanmati',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kamaing',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Momauk',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Waingmaw',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mansi',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Chipwi',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tanai',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tsawlaw',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'WowChon',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nogmung',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Lone Ton',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Lwegel',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sinbo',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dawthponeyan',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shin Bway Yang',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'N Jang Yang',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myo Hla',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kan Paik Ti',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pang War',
                'city_id' => 1,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Loikaw',
                'city_id' => 2,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Demoso',
                'city_id' => 2,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpruso',
                'city_id' => 2,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shadaw',
                'city_id' => 2,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sibu',
                'city_id' => 2,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hoyar',
                'city_id' => 2,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpa-An',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kawkareik',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hlaingbwe',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyain Seikgyi',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpapun',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyondoe',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Leik Tho',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Payathonzu',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myaing Gyi Ngu',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kamarmaung',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zar Ta Pyin',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Paingkyon',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Baw Ga Li',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shan Ywar Thit',
                'city_id' => 3,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hakha',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Falam',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mindat',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tiddim',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Madupi',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tonzang',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'KyeeKar',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Rikawdan',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Rezua',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kanpetlet',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Paletwa',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Htantlang',
                'city_id' => 4,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwebo',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sagaing',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Monywa',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ye-U',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Indaw',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Katha',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kawlin',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Wetlet',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zigon',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Khin-U',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myinmu',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kanbalu',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Wuntho',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mawlaik',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Chaung-U',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Budalin',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kale',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kalewa',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kani',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yinmabin',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tabayin',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tigyaing',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myaung',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyauk Myaung',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mingin',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Salingyi',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Taze',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pinlebu',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ayadaw',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pale',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Banmauk',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Khunhla',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ywar Thit Gyi',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sadaung',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mo Paing Lut',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Htan Par Kway',
                'city_id' => '10',
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dawei',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Palaw',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tanintharyi',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kawthoung',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Maungmakan',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yebyu',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Launglon',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thayetchaung',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Bokpyin',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myeik',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mali Kyun',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Khamaukgyi',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Palaung',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kaleinaung',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myitta',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Karathuri',
                'city_id' => 11,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pyinmana Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Lewe Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tatkon Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Aye Lar Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thar Wut Hti Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ottara Thiri Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zabu Thiri Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zeya Thiri Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pobba Thiri Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dekkhina Thiri Township',
                'city_id' => 12,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tharyarwady',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pyay',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Letpadan',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zingon',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Paungde',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Othakaung',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Gyobingauk',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Okpho',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nettalin',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thegon',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwedaung',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sitkwin',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Moenyo',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pudakaung',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sinmeswel',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Padaung',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Imma',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mataing',
                'city_id' => 13,
            ],

            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'PaukKaung',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Paungdala',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tarpon',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Wethtinkan',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Htintaw',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ayemyatharyar',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'TaungSal',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Okchandpin',
                'city_id' => 13,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Bago',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Taungoo',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nyaunglebin',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Phyu',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpa Yar Gyi',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Daik-U',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pyuntasa',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Waw',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyauktaga',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Penwegon',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yedashe',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tantabin',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwegyin',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myohla',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Oktwin',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ka Nyut Kwin',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pein Za Loke',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kawa',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpaung Taw Thi',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Htantapin',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zay Ya Wa Di',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kywebwe',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyaukkyi',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Inn Ta Kaw',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpa Yar Ka Lay',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Taw Kywe Inn',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yae Ni',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thar Ga Ya',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Swar',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hpa Do',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Madauk',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myit Kyoe',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mone',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Natthankwin',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ayethukha',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kaytumadi',
                'city_id' => 14,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Magway',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yenangyaung',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thayet',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Aunglan',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Taungdwingyi',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Natmauk',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myothit',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Minbu',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Saku',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Salin',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pakokku',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Chauk',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mee Chaung Ye',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kamma',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sinbaungwe',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Minhla',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yesagyo',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sinphyukyi',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myaing',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pauk',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pwintbyu',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Seikphyu',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Minkon',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Salay',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ngape',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mindon',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sidoktaya',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kamma-Myit Chay',
                'city_id' => 15,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Amarapura',
                'city_id' => 16,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Aungmyaythazan',
                'city_id' => 16,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Chanayethazan',
                'city_id' => 16,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Chanmyathazi',
                'city_id' => 16,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mahaaungmyay',
                'city_id' => 16,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Patheingyi',
                'city_id' => 16,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pyigyitagon',
                'city_id' => 16,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mawlamyine',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Moke Ta Ma',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thaton',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyaikto',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mudon',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Chaungzon',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Bilin',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ye',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyaikmaraw',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyaikkhami',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ga doe',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Paung',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thanbyuzayat',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thuwanabwati',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Toung Zoon',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zi Phyu Thaung',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Lamaing',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Khawzar',
                'city_id' => 5,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thingang yun',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yankin',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'South-Okkalapa',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'North-Okkalapa',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dawbon',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thaketa',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tamwe',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mingalar-Taung Nyunt',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pazundaung',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Botataung',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'South-Dagon',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'North-Dagon',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'East-Dagon',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dagon-Seikkan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwepaukkan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyauttada',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pabedan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Latha',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Lanmadaw',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ahlone',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Sanchaung',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kamaryut',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyemyidine',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hlaing',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mayangone',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Bahan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dagon',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Seikkan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Aye Ywar',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thanlyin',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyauktan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kayan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thongwa',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Twantay',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kawhmu',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kungchankone',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dala',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Seikkyi-Kaungto',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Letkhokkone',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyaik Htaw',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Co Co Gyunn',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Tadar',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Insein',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mingalardone',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hmawbi',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hlegu',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Taik-Kyi',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwe Pyi Thar',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hlaing Tharyar',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'HtaukKyant',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Okekan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Darpain',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ahpyauk',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Phaungyi',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Indine',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myaungtagar',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwelinpan',
                'city_id' => 17,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kalaw',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nyaungshwe',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Taunggyi',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hopong',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Aungban',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pindaya',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'He Hoe',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Loilem',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pinlong',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mongpon',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pekon',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Inn Paw Gaung',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwenyaung',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hsihseng',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pinlaung',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nansang',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mongnai',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyethi',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Monghsu',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Laihka',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Lawksawk',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mongkaung',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mawkmai',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mongpan',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ywangan',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thalay',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kunhing',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyauk Ta Lone',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ywarma',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Naungka',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ayetharyar',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Wansalaung',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'NawngNang',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nawngmun',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Intaw',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Naungdaw',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Naungtayar',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Homein',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kengtawng',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mongnaung',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'âKyaukkachar',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'TKyit',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Moebyel',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kho Lam',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kar Li',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mongsan',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'WunHat',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'PhaungLaung',
                'city_id' => 8,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myaungmya',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hinthada',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pathein',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pyapon',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nyaungdon',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Maubin',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myanaung',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyaiklat',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Zalun',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kangkhin',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Yegyi',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Einme',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyonepyaw',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ngathaingchaung',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Athauk',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mawlamyinegyun',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'labutta',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Dedaye',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Danubyu',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Wakema',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Bogale',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Thabaung',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Mazaligone',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyaunggon',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pantanaw',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kyanma',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ingapu',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Htoogyi',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'laymyatnar',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Kangyidaunt',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwe Laung',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Myin Ka Kone',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Chaung Thar',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Hainggyikyun',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Shwethaungyan',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ngwesaung',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Pyinsalu',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Amar',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Nga Yoke Kaung',
                'city_id' => 18,
            ],
            [
                'slug' => $this->generateUniqueSlug(),
                'name' => 'Ka Naung',
                'city_id' => 18,
            ],
        ];

        foreach ($townships as $township) {
            Township::create($township);
        }
    }
}
