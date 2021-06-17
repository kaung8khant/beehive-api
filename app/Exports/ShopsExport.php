<?php

namespace App\Exports;

use App\Models\Shop;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
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
            Carbon::parse($shop->opening_time)->format('g:i A') . ' - ' . Carbon::parse($shop->closing_time)->format('g:i A'),
            $shop->address,
            $shop->is_enable ? '1' : '0',
            $shop->is_official ? '1' : '0',
            $shop->township,
            $shop->city,
            Carbon::parse($shop->opening_time)->format('H:i'),
            Carbon::parse($shop->closing_time)->format('H:i'),
            $shop->latitude,
            $shop->longitude,
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
            'city',
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
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 15,
        ];
    }
}
