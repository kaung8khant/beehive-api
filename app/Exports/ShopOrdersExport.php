<?php

namespace App\Exports;

use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderContact;
use App\Models\Township;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopOrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return ShopOrder::query();
    }

    /**
     * @var ShopOrder $shopOrder
     */
    public function map($shopOrder): array
    {
        $contact = ShopOrderContact::where('shop_order_id', $shopOrder->id);
        $floor = $contact->value('floor') ? ', (' . $contact->value('floor') . ') ,' : ',';
        $address = 'No.' . $contact->value('house_number') . $floor . $contact->value('street_name');
        return [
            $shopOrder->slug,
            $shopOrder->invoice_id,
            Carbon::parse($shopOrder->order_date)->format('M d Y h:i a'),
            $contact->value('customer_name'),
            $contact->value('phone_number'),
            $address,
            $shopOrder->total_amount,
            $shopOrder->payment_mode,
            $shopOrder->delivery_mode,
            $shopOrder->special_instruction,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'invoice_id',
            'order_date',
            'customer',
            'customer_phone_number',
            'address',
            'amount',
            'payment_mode',
            'type',
            'special_instructions',
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
            'E' => 20,
            'F' => 70,
            'G' => 20,
            'H' => 15,
            'I' => 20,
            'J' => 15,
        ];
    }
}
