<?php

namespace App\Exports;

use App\Models\Promocode;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PromocodesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return Promocode::query();
    }

    /**
     * @var Promocode $promocode
     */
    public function map($promocode): array
    {
        return [
            $promocode->slug,
            $promocode->code,
            $promocode->type,
            $promocode->usage,
            $promocode->amount,
            $promocode->description,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'code',
            'type',
            'usage',
            'amount',
            'description',
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 50,
        ];
    }
}
