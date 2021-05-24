<?php

namespace App\Exports;

use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantCategory;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantBranchMenusExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    use Exportable;

    public function __construct(string $params)
    {
        $this->params = $params;
        ini_set('memory_limit', '256M');
    }

    public function query()
    {
        $menus =  Menu::query()->whereHas('restaurantBranches', function ($query) {
            $query->where('slug', $this->params);
        });
        return $menus;
    }

    /**
     * @var Menus $restaurantBranchMenus
     */
    public function map($restaurantBranchMenus): array
    {
        return [
            $restaurantBranchMenus->slug,
            $restaurantBranchMenus->name,
            $restaurantBranchMenus->description,
            $restaurantBranchMenus->price ? $restaurantBranchMenus->price : '0',
            $restaurantBranchMenus->tax ? $restaurantBranchMenus->tax : '0',
            $restaurantBranchMenus->discount ? $restaurantBranchMenus->discount : '0',
            $restaurantBranchMenus->is_enable ? '1' : '0',
            Restaurant::where('id', $restaurantBranchMenus->restaurant_id)->value('name'),
            Restaurant::where('id', $restaurantBranchMenus->restaurant_id)->value('slug'),
            RestaurantCategory::where('id', $restaurantBranchMenus->restaurant_category_id)->value('name'),
            RestaurantCategory::where('id', $restaurantBranchMenus->restaurant_category_id)->value('slug'),
        ];
    }

    public function headings(): array
    {
        return [
            'slug',
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
