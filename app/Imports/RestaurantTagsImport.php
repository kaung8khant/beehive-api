<?php

namespace App\Imports;

use App\Models\RestaurantTag;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class RestaurantTagsImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts
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
        return RestaurantTag::create([
            'slug' => isset($row['slug']) ? $row['slug'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
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
            'name' => 'required|unique:restaurant_tags',
        ];
    }
}
