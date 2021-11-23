<?php

namespace App\Exports;

use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryMenusExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(string $params)
    {
        $this->params = $params;
    }

    public function query()
    {
        $category = RestaurantCategory::where('slug', $this->params)->firstOrFail();

        return  MenuVariant::with("menu")->whereHas('menu', function ($q) use ($category) {
            $q->where('restaurant_category_id', $category->id);
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
            Restaurant::where('id', $menuVariant->menu->restaurant_id)->value('name'),
            Restaurant::where('id', $menuVariant->menu->restaurant_id)->value('slug'),
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
            'restaurant',
            'restaurant_slug',
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
            'F' => 10,
            'G' => 10,
            'H' => 25,
            'I' => 15,
            'J' => 25,
            'K' => 25,
            'L' => 25,
        ];
    }
}
