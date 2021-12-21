<?php

namespace App\Exports;

use App\Models\RestaurantBranch;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantBranchesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return RestaurantBranch::with(['restaurant']);
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
            Carbon::parse($restaurantBranch->opening_time)->format('g:i A') . ' - ' . Carbon::parse($restaurantBranch->closing_time)->format('g:i A'),
            $restaurantBranch->is_enable ? '1' : '0',
            $restaurantBranch->free_delivery ? '1' : '0',
            $restaurantBranch->township,
            $restaurantBranch->city,
            $restaurantBranch->address,
            $restaurantBranch->restaurant->slug,
            $restaurantBranch->restaurant->name,
            Carbon::parse($restaurantBranch->opening_time)->format('H:i'),
            Carbon::parse($restaurantBranch->closing_time)->format('H:i'),
            $restaurantBranch->latitude,
            $restaurantBranch->longitude,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'contact_number',
            'opening_hours',
            'is_enable',
            'free_delivery',
            'township',
            'city',
            'address',
            'restaurant_slug',
            'restaurant',
            'opening_time',
            'closing_time',
            'latitude',
            'longitude',
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
            'O' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 50,
            'C' => 20,
            'D' => 50,
            'E' => 15,
            'F' => 15,
            'G' => 20,
            'H' => 20,
            'I' => 100,
            'J' => 20,
            'K' => 20,
            'L' => 15,
            'M' => 15,
            'N' => 15,
            'O' => 15,
        ];
    }
}
