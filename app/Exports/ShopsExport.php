<?php

namespace App\Exports;

use App\Models\Shop;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Models\Township;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ShopsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }

    public function query()
    {
        return Shop::query();
    }

    /**
     * @var Shop $shop
     */
    public function map($shop): array
    {
        return [
            $shop->slug,
            $shop->name,
            $shop->contact_number,
            Carbon::parse($shop->opening_time)->format('g:i A').' - '.Carbon::parse($shop->closing_time)->format('g:i A'),
            $shop->address,
            $shop->is_enable ? '1' : '0',
            $shop->is_official ? '1' : '0',
            Township::where('id', $shop->township_id)->value('name'),
            Township::where('id', $shop->township_id)->value('slug'),
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
            'is_official',
            'township',
            'township_slug',
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
            'G' => 10,
            'H' => 20,
            'I' => 15,
        ];
    }
}
