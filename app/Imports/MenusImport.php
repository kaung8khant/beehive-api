<?php

namespace App\Imports;

use App\Helpers\StringHelper;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class MenusImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts
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
            'slug' => isset($row['slug']) ? $row['slug'] : StringHelper::generateUniqueSlug(),
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

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return 'slug';
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'tax' => 'required|numeric',
            'discount' => 'required|numeric',
            'is_enable' => 'required|boolean',
            'restaurant_slug' => 'required|exists:App\Models\Restaurant,slug',
            'restaurant_category_slug' => 'required|exists:App\Models\RestaurantCategory,slug',
        ];
    }
}
