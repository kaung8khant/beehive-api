<?php

namespace App\Exports;

use App\Models\Menu;
use App\Models\MenuVariant;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantBranchMenusExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(string $params)
    {
        $this->params = $params;
    }

    public function query()
    {
        return  MenuVariant::with("menu", 'menu.restaurantBranches', 'menu.restaurant', 'menu.restaurantCategory')->whereHas('menu.restaurantBranches', function ($q) {
            $q->where('slug', $this->params);
        });
    }

    public function stringifyVariant($variants)
    {
        $implode = array();
        foreach ($variants as $variant) {
            $implode[] = implode(':', $variant);
        }
        return implode(' / ', $implode);
    }

    /**
     * @var Menu $menu
     */
    public function map($menuVariant): array
    {
        return [
            $menuVariant->menu->slug,
            $menuVariant->slug,
            $menuVariant->menu->name,
            $menuVariant->menu->description,
            $menuVariant->menu->is_enable ? '1' : '0',
            $this->stringifyVariant($menuVariant->variant),
            $menuVariant->price ? $menuVariant->price : '0',
            $menuVariant->tax ? $menuVariant->tax : '0',
            $menuVariant->discount ? $menuVariant->discount : '0',
            $menuVariant->is_enable ? '1' : '0',
            $menuVariant->menu->restaurant->slug,
            $menuVariant->menu->restaurant->name,
            $menuVariant->menu->restaurant->slug,
            $menuVariant->menu->restaurant->name,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'menu_variant_slug',
            'name',
            'description',
            'is_enable',
            'variant',
            'price',
            'tax',
            'discount',
            'variant_is_enable',
            'restaurant_slug',
            'restaurant',
            'restaurant_category_slug',
            'restaurant_category',
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
            'L' => ['alignment' => ['horizontal' => 'center']],
            'M' => ['alignment' => ['horizontal' => 'center']],
            'N' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 45,
            'D' => 45,
            'E' => 10,
            'F' => 30,
            'G' => 10,
            'H' => 25,
            'I' => 15,
            'J' => 25,
            'K' => 25,
            'L' => 25,
            'M' => 25,
            'N' => 25,
        ];
    }
}
