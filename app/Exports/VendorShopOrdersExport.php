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

class VendorShopOrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(string $params)
    {
        $this->params = $params;
        ini_set('memory_limit', '256M');
    }

    public function query()
    {
        $shop = Shop::where('slug', $this->params)->firstOrFail();

        return ShopOrder::query()->whereHas('vendors', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id);
        });
    }

    /**
     * @var ShopOrder $vendorShopOrder
     */
    public function map($vendorShopOrder): array
    {
        $contact = ShopOrderContact::where('shop_order_id', $vendorShopOrder->id);
        $floor = $contact->value('floor') ? ', (' . $contact->value('floor') . ') ,' : ',';
        $address = 'No.' . $contact->value('house_number') . $floor . $contact->value('street_name');
        return [
            $vendorShopOrder->slug,
            $vendorShopOrder->invoice_id,
            Carbon::parse($vendorShopOrder->order_date)->format('M d Y  h:i a'),
            $contact->value('customer_name'),
            $contact->value('phone_number'),
            $address,
            $vendorShopOrder->total_amount,
            $vendorShopOrder->payment_mode,
            $vendorShopOrder->delivery_mode,
            $vendorShopOrder->special_instruction,
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
