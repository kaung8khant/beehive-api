<?php

namespace App\Exports;

// use App\Models\Menu;
use App\Models\MenuVariant;
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
        return MenuVariant::query()->with("menu");
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
            $this->stringifyVariant($menuVariant->variant),
            $menuVariant->price ? $menuVariant->price : '0',
            $menuVariant->tax ? $menuVariant->tax : '0',
            $menuVariant->discount ? $menuVariant->discount : '0',
            $menuVariant->is_enable ? '1' : '0',
            Restaurant::where('id', $menuVariant->menu->restaurant_id)->value('name'),
            Restaurant::where('id', $menuVariant->menu->restaurant_id)->value('slug'),
            RestaurantCategory::where('id', $menuVariant->menu->restaurant_category_id)->value('name'),
            RestaurantCategory::where('id', $menuVariant->menu->restaurant_category_id)->value('slug'),
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'menu_variant_slug',
            'name',
            'description',
            'variant',
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
        ];
    }
}
