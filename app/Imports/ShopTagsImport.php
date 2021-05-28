<?php

namespace App\Imports;

use App\Models\ShopTag;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class ShopTagsImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation
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
        return new ShopTag([
            'id' => isset($row['id']) && $this->transformSlugToId($row['id']),
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
            'name' => 'required|unique:shop_tags',
        ];
    }

    public function transformSlugToId($value)
    {
        $shopTag = ShopTag::where('slug', $value)->first();

        if (!$shopTag) {
            return null;
        }

        return $shopTag->id;
    }
}
