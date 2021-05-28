<?php

namespace App\Imports;

use App\Models\Brand;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class BrandsImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation
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
        $brandId = isset($row['id']) && $this->transformSlugToId($row['id']);
        return new Brand([
            'id' => $brandId,
            'slug' => isset($row['id']) ? $row['id'] : StringHelper::generateUniqueSlug(),
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
            'name' => 'required|unique:brands',
        ];
    }

    public function transformSlugToId($value)
    {
        $brand = Brand::where('slug', $value)->first();

        if (!$brand) {
            return null;
        }

        return $brand->id;
    }
}
