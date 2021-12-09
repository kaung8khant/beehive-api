<?php

namespace App\Exports;

use App\Models\ShopMainCategory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopMainCategoriesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return ShopMainCategory::query();
    }

    /**
     * @var ShopMainCategory $shopMainCategory
     */
    public function map($shopMainCategory): array
    {
        return [
            $shopMainCategory->slug,
            $shopMainCategory->code,
            $shopMainCategory->name,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'code',
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
            'C' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 40,
        ];
    }
}
