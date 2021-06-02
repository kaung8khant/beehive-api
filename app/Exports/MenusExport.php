<?php

namespace App\Exports;

use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MenusExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return Menu::query();
    }

    /**
     * @var Menu $menu
     */
    public function map($menu): array
    {
        return [
            $menu->slug,
            $menu->name,
            $menu->description,
            $menu->price ? $menu->price : '0',
            $menu->tax ? $menu->tax : '0',
            $menu->discount ? $menu->discount : '0',
            $menu->is_enable ? '1' : '0',
            Restaurant::where('id', $menu->restaurant_id)->value('name'),
            Restaurant::where('id', $menu->restaurant_id)->value('slug'),
            RestaurantCategory::where('id', $menu->restaurant_category_id)->value('name'),
            RestaurantCategory::where('id', $menu->restaurant_category_id)->value('slug'),
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'description',
            'price',
            'tax',
            'discount',
            'is_enable',
            'restaurant',
            'restaurant_slug',
            'restaurant_category',
            'restaurant_category_slug',
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
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'G' => ['alignment' => ['horizontal' => 'center']],
            'H' => ['alignment' => ['horizontal' => 'center']],
            'I' => ['alignment' => ['horizontal' => 'center']],
            'J' => ['alignment' => ['horizontal' => 'center']],
            'K' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 45,
            'D' => 10,
            'E' => 10,
            'F' => 10,
            'G' => 10,
            'H' => 25,
            'I' => 15,
            'J' => 25,
            'K' => 25,
        ];
    }
}
