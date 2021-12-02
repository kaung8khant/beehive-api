<?php

namespace App\Exports\Sales;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopOrderItem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopProductSalesExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $from;
    protected $to;

    protected $result;
    protected $amountSum;
    protected $totalAmountSum;
    protected $commissionSum;
    protected $commissionCtSum;
    protected $balanceSum;
    protected $key;

    public function __construct($param, $from, $to)
    {
        $this->param = $param;
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        $shop = Shop::where('slug', $this->param)->first();

        $shopOrderItems = ShopOrderItem::whereHas('vendor.shopOrder', function ($query) {
            $query->whereBetween('order_date', [$this->from, $this->to])->where('order_status', '!=', 'cancelled');
        })->where('shop_id', $shop->id)->get();

        $this->result=$shopOrderItems->map(function ($item, $key) {
            $amount = $item->vendor->shopOrder->order_status == 'cancelled' ? '0' :$item->amount * $item->quantity;
            $commission =  $item->vendor->shopOrder->order_status == 'cancelled' ? '0' : $item->commission;
            $commissionCt = $commission * 0.05;
            $totalAmount =  $item->vendor->shopOrder->order_status == 'cancelled' ? '0' :$item->total_amount;
            $balance = $totalAmount - $commissionCt;
            $commercialTax =  $item->vendor->shopOrder->order_status != 'cancelled'  && $item->tax ? $item->tax * $item->quantity : '0';
            $discount = $item->vendor->shopOrder->order_status != 'cancelled'  && $item->discount ? $item->discount * $item->quantity : 0;
            $quantity = $item->vendor->shopOrder->order_status == 'cancelled'  ? '0': $item->quantity;
            $this->key += 1;
            $this->amountSum += $amount;
            $this->totalAmountSum += $totalAmount;
            $this->commissionSum += $commission;
            $this->commissionCtSum += $commissionCt;
            $this->balanceSum += $balance;
            $product = Product::where('id', $item->product_id)->first();

            return [
                $this->key,
                $item->vendor->shopOrder->invoice_id,
                Carbon::parse($item->vendor->shopOrder->order_date)->format('M d Y h:i a'),
                $product->code,
                $item->product_name,
                implode(',', array_map(function ($n) {
                    return $n['value'];
                }, $item->variant)),
                $item->amount,
                $item->vendor_price,
                $quantity,
                $amount,
                $commercialTax ? $commercialTax : '0',
                $discount ? $discount : '0',
                $totalAmount,
                $commission ? $commission : '0',
                $commissionCt ? $commissionCt : '0',
                round($balance),
                $item->vendor->shopOrder->payment_mode,
                $item->vendor->shopOrder->payment_status,
                $item->vendor->shopOrder->payment_reference,
                $item->vendor->shopOrder->order_status,
                $item->vendor->shopOrder->special_instruction,
                $item->vendor->shopOrder->contact->customer_name,
                $item->vendor->shopOrder->contact->phone_number,
            ];
        });

        return $this->result;
    }

    public function headings(): array
    {
        $reportDate = Carbon::now()->format('d M Y');
        $from = Carbon::parse($this->from)->format('d M Y');
        $to = Carbon::parse($this->to)->format('d M Y');

        return [
            [],
            ['Beehive Ecommerce'],
            ['Date:', $reportDate],
            ['Delivery Report:', $from . ' to ' . $to],
            [],
            [
                'no.',
                'invoice id',
                'order date',
                'code',
                'product',
                'variant',
                'selling price',
                'vendor price',
                'quantity',
                'revenue',
                'commercial tax',
                'discount',
                "total amount\n(tax inclusive)",
                'commission',
                'ct on commision',
                'balance',
                'payment mode',
                'payment status',
                'payment reference',
                'order status',
                'special instructions',
                'customer name',
                'phone number',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 20,
            'C' => 30,
            'D' => 30,
            'E' => 30,
            'F' => 30,
            'G' => 20,
            'H' => 15,
            'I' => 15,
            'J' => 20,
            'K' => 15,
            'L' => 15,
            'M' => 20,
            'N' => 20,
            'O' => 20,
            'P' => 20,
            'Q' => 20,
            'R' => 20,
            'S' => 20,
            'T' => 20,
            'U' => 20,
            'V' => 20,
            'W' => 20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(79);

        return [
            'A' => ['alignment' => ['horizontal' => 'center']],
            'B' => ['alignment' => ['horizontal' => 'center']],
            'C' => ['alignment' => ['horizontal' => 'center']],
            'D' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'Q' => ['alignment' => ['horizontal' => 'center']],
            'R' => ['alignment' => ['horizontal' => 'center']],
            'S' => ['alignment' => ['horizontal' => 'center']],
            'T' => ['alignment' => ['horizontal' => 'center']],
            'U' => ['alignment' => ['horizontal' => 'center']],
            'V' => ['alignment' => ['horizontal' => 'center']],
            'W' => ['alignment' => ['horizontal' => 'center']],
            2 => ['alignment' => ['horizontal' => 'left']],
            3 => ['alignment' => ['horizontal' => 'left']],
            4 => ['alignment' => ['horizontal' => 'left']],
            6 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
            'M' => '#,##0',
            'N' => '#,##0',
            'O' => '#,##0',
            'P' => '#,##0',
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Beehive');
        $drawing->setDescription('Beehive Logo');
        $drawing->setPath(storage_path('app/images/beehive-logo.png'));
        $drawing->setHeight(100);
        $drawing->setOffsetX(2);
        $drawing->setOffsetY(2);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = count($this->result) + 6 + 1;

                $event->sheet->getStyle(sprintf('J%d', $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('J%d', $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('M%d:P%d', $lastRow - 1, $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('M%d:P%d', $lastRow, $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('P%d', $lastRow))->getFont()->setBold(true);

                $event->sheet->setCellValue(sprintf('J%d', $lastRow), $this->amountSum);
                $event->sheet->setCellValue(sprintf('M%d', $lastRow), $this->totalAmountSum);
                $event->sheet->setCellValue(sprintf('N%d', $lastRow), $this->commissionSum);
                $event->sheet->setCellValue(sprintf('O%d', $lastRow), $this->commissionCtSum);
                $event->sheet->setCellValue(sprintf('P%d', $lastRow), $this->balanceSum);

                $event->sheet->getStyle($lastRow)->getNumberFormat()->setFormatCode('#,##0');

                $month = Carbon::parse($this->to)->format('F');

                $event->sheet->getStyle(sprintf('A%d:A%d', $lastRow + 1, $lastRow + 2))->getAlignment()->setHorizontal('left');
                $event->sheet->getStyle(sprintf('A%d:A%d', $lastRow + 1, $lastRow + 2))->getFont()->getColor()->setRGB('808080');
                $event->sheet->getStyle(sprintf('A%d:A%d', $lastRow + 1, $lastRow + 2))->getFont()->setSize(10);
                $event->sheet->setCellValue(sprintf('A%d', $lastRow + 1), '* Commercial Tax are not collected for the sales of ' . $month . '.');
                $event->sheet->setCellValue(sprintf('A%d', $lastRow + 2), '* Commision to Beehive will be waived for the month of ' . $month . '.');

                $event->sheet->getStyle('A6:S6')->getAlignment()->setHorizontal('center');
            },
        ];
    }

    public function title(): string
    {
        return 'Shop Product Sales report';
    }
}
