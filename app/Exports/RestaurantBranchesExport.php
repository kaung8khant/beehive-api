<?php

namespace App\Exports;

use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\Township;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class RestaurantBranchesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }

    public function query()
    {
        return RestaurantBranch::query();
    }

    /**
     * @var RestaurantBranch $restaurantBranch
     */
    public function map($restaurantBranch): array
    {
        return [
            $restaurantBranch->slug,
            $restaurantBranch->name,
            $restaurantBranch->contact_number,
            Carbon::parse($restaurantBranch->opening_time)->format('g:i A').' - '.Carbon::parse($restaurantBranch->closing_time)->format('g:i A'),
            $restaurantBranch->address,
            $restaurantBranch->is_enable ? '1' : '0',
            Township::where('id', $restaurantBranch->township_id)->value('name'),
            Township::where('id', $restaurantBranch->township_id)->value('slug'),
            Restaurant::where('id', $restaurantBranch->restaurant_id)->value('name'),
            Restaurant::where('id', $restaurantBranch->restaurant_id)->value('slug'),
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'contact_number',
            'opening_hours',
            'address',
            'is_enable',
            'township',
            'township_slug',
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 50,
            'C' => 20,
            'D' => 50,
            'E' => 100,
            'F' => 10,
            'G' => 20,
            'H' => 15,
            'I' => 20,
            'J' => 15,
        ];
    }
}
