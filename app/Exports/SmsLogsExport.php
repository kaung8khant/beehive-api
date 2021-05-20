<?php

namespace App\Exports;

use App\Models\SmsLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SmsLogsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
    }

    public function query()
    {
        return SmsLog::query();
    }

    /**
     * @var SmsLog $smsLog
     */
    public function map($smsLog): array
    {
        return [
            $smsLog->message_id,
            $smsLog->phone_number,
            $smsLog->status,
            $smsLog->type,
        ];
    }

    public function headings(): array
    {
        return [
            'message_id',
            'phone_number',
            'status',
            'type',
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 20,
            'C' => 20,
            'D' => 20,
        ];
    }
}
