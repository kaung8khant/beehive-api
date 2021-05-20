<?php

namespace App\Imports;

use App\Models\ShopSubCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use App\Models\ShopCategory;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class ShopSubCategoriesImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation
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
        return new ShopSubCategory([
            'slug' => isset($row['slug']) ? $row['slug'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
            'shop_category_id' =>ShopCategory::where('slug', $row['shop_category_slug'])->value('id'),
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
            'name' => 'required|unique:shop_sub_categories',
            'shop_category_slug' => 'required|exists:App\Models\ShopCategory,slug',
        ];
    }
}
