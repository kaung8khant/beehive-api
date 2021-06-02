<?php

namespace App\Exports;

use App\Models\ShopCategory;
use App\Models\ShopSubCategory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopSubCategoriesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return ShopSubCategory::query();
    }

    /**
     * @var ShopSubCategory $ShopSubCategory
     */
    public function map($shopSubCategory): array
    {
        return [
            $shopSubCategory->slug,
            $shopSubCategory->name,
            ShopCategory::where('id', $shopSubCategory->shop_category_id)->value('slug'),
            ShopCategory::where('id', $shopSubCategory->shop_category_id)->value('name'),
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'shop_category_slug',
            'shop_category',
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
            'D' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 40,
            'D' => 40,
        ];
    }
}
