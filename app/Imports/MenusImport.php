<?php

namespace App\Imports;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MenusImport implements ToModel, WithHeadingRow, WithChunkReading
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Menu([
            'slug' => StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => $row['price'],
            'tax' => $row['tax'],
            'discount' => $row['discount'],
            'is_enable' => $row['is_enable'],
            'restaurant_id' => Restaurant::where('slug', $row['restaurant_slug'])->value('id'),
            'restaurant_category_id' => RestaurantCategory::where('slug', $row['restaurant_category_slug'])->value('id'),
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
