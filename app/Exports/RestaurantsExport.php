<?php

namespace App\Exports;

use App\Models\Restaurant;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }

    public function query()
    {
        return Restaurant::query();
    }

    /**
     * @var Restaurant $restaurant
     */
    public function map($restaurant): array
    {
        return [
            $restaurant->slug,
            $restaurant->name,
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
