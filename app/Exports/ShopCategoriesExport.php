<?php

namespace App\Exports;

use App\Models\ShopCategory;
use App\Models\ShopMainCategory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopCategoriesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return ShopCategory::query();
    }

    /**
     * @var ShopCategory $shopCategory
     */
    public function map($shopCategory): array
    {
        return [
            $shopCategory->code,
            $shopCategory->name,
            ShopMainCategory::where('id', $shopCategory->shop_main_category_id)->value('code'),
        ];
    }

    public function headings(): array
    {
        return [
            'code',
            'name',
            'product_type_code',
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
            'B' => 40,
            'C' => 40,
        ];
    }
}
