<?php

namespace App\Exports;

use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderContact;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderVendor;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VendorShopOrdersExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(string $params)
    {
        $this->params = $params;
        ini_set('memory_limit', '256M');
    }

    public function collection()
    {
        $shop = Shop::where('slug', $this->params)->firstOrFail();

        $shopOrderItems= ShopOrderItem::where('shop_id', $shop ->id)->get();

        $result= $shopOrderItems->map(function ($item) {
            $shopOrderVendor = ShopOrderVendor::where('id', $item->shop_order_vendor_id)->first();
            $shopOrder = ShopOrder::find($shopOrderVendor->shop_order_id)->toArray();
            $shopOrder['id']=$shopOrderVendor->shop_order_id;
            unset($shopOrder['vendors']);
            $item->shop_order = $shopOrder;
            return $item;
        });

        $exportData=[];

        foreach ($result as $shopOrderItem) {
            $contact = ShopOrderContact::where('shop_order_id', $shopOrderItem['shop_order']['id']);
            $floor = $contact->value('floor') ? ', (' . $contact->value('floor') . ') ,' : ',';
            $address = 'No.' . $contact->value('house_number') . $floor . $contact->value('street_name');
            $subTotal=($shopOrderItem->amount -  $shopOrderItem->discount) *  $shopOrderItem->quantity;
            $data=[
            'id'=>$shopOrderItem['shop_order']['slug'],
            'invoice_id'=>$shopOrderItem['shop_order']['invoice_id'],
            'order_date'=>Carbon::parse($shopOrderItem['shop_order']['order_date'])->format('M d Y h:i a'),
            'customer'=>$contact->value('customer_name'),
            'customer_phone_number'=>$contact->value('phone_number'),
            'address'=> $address,
            'product_name'=> $shopOrderItem->product_name,
            'price'=> $shopOrderItem->amount ? $shopOrderItem->amount : '0',
            'vendor_price'=>$shopOrderItem->vendor_price ? $shopOrderItem->vendor_price : '0',
            'tax'=> $shopOrderItem->tax ? $shopOrderItem->tax : '0',
            'discount'=> $shopOrderItem->discount ? $shopOrderItem->discount : '0',
            'quantity'=> $shopOrderItem->quantity,
            'subTotal'=>$subTotal,
            'total'=>$shopOrderItem['shop_order']['total_amount'],
            'payment_mode'=>$shopOrderItem['shop_order']['payment_mode'],
            'type'=>$shopOrderItem['shop_order']['delivery_mode'],
            'special_instructions'=>$shopOrderItem['shop_order']['special_instruction'],
        ];
            array_push($exportData, $data);
        }

        return collect([
                $exportData
        ]);
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
            'product_name',
            'price',
            'vendor_price',
            'tax',
            'discount',
            'quantity',
            'subTotal',
            'total',
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
            'K' => ['alignment' => ['horizontal' => 'center']],
            'L' => ['alignment' => ['horizontal' => 'center']],
            'M' => ['alignment' => ['horizontal' => 'center']],
            'N' => ['alignment' => ['horizontal' => 'center']],
            'O' => ['alignment' => ['horizontal' => 'center']],
            'P' => ['alignment' => ['horizontal' => 'center']],
            'Q' => ['alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 15,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 70,
            'G' => 20,
            'H' => 15,
            'I' => 20,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 15,
            'O' => 15,
            'P' => 15,
            'Q' => 30,
        ];
    }
}
