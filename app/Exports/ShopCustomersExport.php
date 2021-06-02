<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Shop;
use App\Models\ShopOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopCustomersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        $shop = Shop::where('slug', $this->params)->firstOrFail();

        $orderList = ShopOrder::whereHas('vendors', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id);
        })->pluck('id');

        $customers = Customer::query()->whereIn('id', $orderList);
        return $customers;
    }
    /**
     * @var Customer $shopCustomer
     */
    public function map($shopCustomer): array
    {
        return [
            $shopCustomer->slug,
            $shopCustomer->name,
            $shopCustomer->email,
            $shopCustomer->phone_number,
            $shopCustomer->gender,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone_number',
            'gender',
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 20,
            'D' => 50,
            'E' => 15,
        ];
    }
}
