<?php

namespace App\Exports;

use App\Models\RestaurantTag;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantTagsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return RestaurantTag::query();
    }

    /**
     * @var RestaurantTag $restaurantTag
     */
    public function map($restaurantTag): array
    {
        return [
            $restaurantTag->slug,
            $restaurantTag->name,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
            'A' => ['alignment' => ['horizontal' => 'center']],
            'B' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
        ];
    }
}
