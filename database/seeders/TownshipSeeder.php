<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Township;

class TownshipSeeder extends Seeder
{
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
                     "name" => "Myitkyina",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Bhamo",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mogaung",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mohnyin",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nanmar",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hopin",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpakan",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwegu",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sadong",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nanmati",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kamaing",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Momauk",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Waingmaw",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mansi",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Chipwi",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tanai",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tsawlaw",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "WowChon",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nogmung",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Lone Ton",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Lwegel",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sinbo",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dawthponeyan",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shin Bway Yang",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "N Jang Yang",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myo Hla",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kan Paik Ti",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pang War",
                     "city_slug" => "1",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Loikaw",
                     "city_slug" => "2",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Demoso",
                     "city_slug" => "2",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpruso",
                     "city_slug" => "2",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shadaw",
                     "city_slug" => "2",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sibu",
                     "city_slug" => "2",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hoyar",
                     "city_slug" => "2",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpa-An",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kawkareik",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hlaingbwe",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyain Seikgyi",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpapun",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyondoe",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Leik Tho",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Payathonzu",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myaing Gyi Ngu",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kamarmaung",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zar Ta Pyin",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Paingkyon",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Baw Ga Li",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shan Ywar Thit",
                     "city_slug" => "3",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hakha",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Falam",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mindat",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tiddim",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Madupi",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tonzang",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "KyeeKar",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Rikawdan",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Rezua",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kanpetlet",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Paletwa",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Htantlang",
                     "city_slug" => "4",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwebo",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sagaing",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Monywa",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ye-U",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Indaw",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Katha",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kawlin",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Wetlet",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zigon",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Khin-U",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myinmu",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kanbalu",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Wuntho",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mawlaik",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Chaung-U",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ahlone",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Budalin",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kale",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kalewa",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kani",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yinmabin",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tabayin",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tigyaing",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myaung",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyauk Myaung",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mingin",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Salingyi",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Taze",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pinlebu",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ayadaw",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pale",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Banmauk",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Khunhla",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ywar Thit Gyi",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sadaung",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mo Paing Lut",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Htan Par Kway",
                     "city_slug" => "10",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dawei",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Palaw",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tanintharyi",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kawthoung",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Maungmakan",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yebyu",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Launglon",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thayetchaung",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Bokpyin",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myeik",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mali Kyun",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Khamaukgyi",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Palaung",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kaleinaung",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myitta",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Karathuri",
                     "city_slug" => "11",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pyinmana Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Lewe Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tatkon Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Aye Lar Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thar Wut Hti Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ottara Thiri Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zabu Thiri Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zeya Thiri Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pobba Thiri Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dekkhina Thiri Township",
                     "city_slug" => "12",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "tharyarwady",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pyay",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Letpadan",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zingon",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Paungde",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Othakaung",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Gyobingauk",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Okpho",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nettalin",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thegon",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwedaung",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sitkwin",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Moenyo",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pudakaung",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sinmeswel",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Padaung",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Imma",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mataing",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Minhla",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "PaukKaung",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Paungdala",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tarpon",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tharyarwady",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Wethtinkan",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Htintaw",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ayemyatharyar",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "TaungSal",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Okchandpin",
                     "city_slug" => "13",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Bago",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Taungoo",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nyaunglebin",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Phyu",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpa Yar Gyi",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Daik-U",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pyuntasa",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Waw",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyauktaga",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Penwegon",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yedashe",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tantabin",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwegyin",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myohla",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Oktwin",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ka Nyut Kwin",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pein Za Loke",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kawa",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpaung Taw Thi",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Htantapin",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zay Ya Wa Di",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kywebwe",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyaukkyi",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Inn Ta Kaw",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpa Yar Ka Lay",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Taw Kywe Inn",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yae Ni",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thar Ga Ya",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Swar",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hpa Do",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Madauk",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myit Kyoe",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mone",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Natthankwin",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ayethukha",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kaytumadi",
                     "city_slug" => "14",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Magway",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yenangyaung",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thayet",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Aunglan",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Taungdwingyi",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Natmauk",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myothit",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Minbu",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Saku",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Salin",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pakokku",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Chauk",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mee Chaung Ye",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kamma",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sinbaungwe",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Minhla",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yesagyo",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sinphyukyi",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myaing",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pauk",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pwintbyu",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Seikphyu",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Minkon",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Salay",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ngape",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mindon",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sidoktaya",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kamma-Myit Chay",
                     "city_slug" => "15",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mawlamyine",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Moke Ta Ma",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thaton",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyaikto",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mudon",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Chaungzon",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Bilin",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ye",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyaikmaraw",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyaikkhami",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ga doe",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Paung",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thanbyuzayat",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thuwanabwati",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Toung Zoon",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zi Phyu Thaung",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Lamaing",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Khawzar",
                     "city_slug" => "5",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thingang yun",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yankin",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "South-Okkalapa",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "North-Okkalapa",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dawbon",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thaketa",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tamwe",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mingalar-Taung Nyunt",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pazundaung",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Botataung",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "South-Dagon",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "North-Dagon",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "East-Dagon",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dagon-Seikkan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwepaukkan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyauttada",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pabedan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Latha",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Lanmadaw",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ahlone",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Sanchaung",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kamaryut",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyemyidine",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hlaing",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mayangone",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Bahan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dagon",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Seikkan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Aye Ywar",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thanlyin",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyauktan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kayan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thongwa",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Twantay",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kawhmu",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kungchankone",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dala",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Seikkyi-Kaungto",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Letkhokkone",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyaik Htaw",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Co Co Gyunn",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Tadar",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Insein",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mingalardone",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hmawbi",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hlegu",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Taik-Kyi",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Htantapin",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwe Pyi Thar",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hlaing Tharyar",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "HtaukKyant",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Okekan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Darpain",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ahpyauk",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Phaungyi",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Indine",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myaungtagar",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwelinpan",
                     "city_slug" => "17",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kalaw",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nyaungshwe",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Taunggyi",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hopong",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Aungban",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pindaya",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "He Hoe",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Loilem",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Laihka",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pinlong",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mongpon",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pekon",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Inn Paw Gaung",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwenyaung",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hsihseng",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pinlaung",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nansang",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mongnai",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyethi",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Monghsu",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Laihka",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Lawksawk",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mongkaung",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mawkmai",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mongpan",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ywangan",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thalay",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kunhing",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyauktan",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyauk Ta Lone",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ywarma",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Naungka",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ayetharyar",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Wansalaung",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "NawngNang",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nawngmun",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Intaw",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Naungdaw",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Naungtayar",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Homein",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kengtawng",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mongnaung",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "âKyaukkachar",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "TKyit",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Moebyel",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pekon",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kho Lam",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kar Li",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mongsan",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "WunHat",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "PhaungLaung",
                     "city_slug" => "8",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myaungmya",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hinthada",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pathein",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pyapon",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nyaungdon",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Maubin",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myanaung",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyaiklat",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Zalun",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kangkhin",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Yegyi",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Einme",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyonepyaw",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ngathaingchaung",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Athauk",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mawlamyinegyun",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "labutta",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Dedaye",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Danubyu",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Wakema",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Bogale",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Thabaung",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "ááá¯áá±á¬",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyaunggon",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pantanaw",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kyanma",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ingapu",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Htoogyi",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "laymyatnar",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Mazaligone",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Kangyidaunt",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwe Laung",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Myin Ka Kone",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Chaung Thar",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Hainggyikyun",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Shwethaungyan",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ngwesaung",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Pyinsalu",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Amar",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Nga Yoke Kaung",
                     "city_slug" => "18",
                 ],
                 [
                     'slug' => $this->generateUniqueSlug(),
                     "name" => "Ka Naung",
                     "city_slug" => "18",
                 ]

        ];

        foreach ($townships as $township) {
            Township::create($township);
        }

    }
}