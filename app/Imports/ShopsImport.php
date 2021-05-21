<?php

namespace App\Imports;

use App\Models\Shop;
use App\Models\Township;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Helpers\StringHelper;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Propaganistas\LaravelPhone\PhoneNumber;
use Maatwebsite\Excel\Concerns\WithValidation;

class ShopsImport implements ToModel, WithHeadingRow, WithChunkReading, WithUpserts, WithValidation
{
    use Importable;

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
        return new Shop([
            'slug' => isset($row['slug']) ? $row['slug'] : StringHelper::generateUniqueSlug(),
            'name' => $row['name'],
            'contact_number' => PhoneNumber::make($row['contact_number'], 'MM'),
            'opening_time' => $row['opening_time'],
            'closing_time' => $row['closing_time'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'address' => $row['address'],
            'is_enable' => $row['is_enable'],
            'is_official' => $row['is_official'],
            'township_id' => Township::where('slug', $row['township_slug'])->value('id'),
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
            'name' => 'required|unique:shops',
            'is_enable' => 'required|boolean',
            'is_official' => 'required|boolean',
            'address' => 'nullable',
            'contact_number' => 'required|phone:MM',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'township_slug' => 'nullable|exists:App\Models\Township,slug',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'contact_number.phone' => 'Invalid Phone Number',
        ];
    }
}
