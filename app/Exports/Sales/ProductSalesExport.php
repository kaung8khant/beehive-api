<?php

namespace App\Exports\Sales;

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

class ProductSalesExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $from;
    protected $to;

    protected $result;
    protected $amountSum;
    protected $totalAmountSum;
    protected $commissionSum;
    protected $commissionCtSum;
    protected $balanceSum;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        $shopOrderItems = ShopOrderItem::whereHas('vendor.shopOrder', function ($query) {
            $query->whereBetween('order_date', [$this->from, $this->to]);
        })->get();

        $this->result = $shopOrderItems->map(function ($item, $key) {
            $shop = Shop::where('id', $item->shop_id)->first();

            $amount = $item->vendor->shopOrder->order_status == 'cancelled' ? '0' : ($item->amount * $item->quantity);
            $commission =  $item->vendor->shopOrder->order_status == 'cancelled' ? '0' : $item->commission;
            $commissionCt = $commission * 0.05;
            $totalAmount = $item->vendor->shopOrder->order_status == 'cancelled' ? '0' : $item->total_amount;
            $balance = $totalAmount - $commissionCt;

            $this->amountSum += $amount;
            $this->totalAmountSum += $totalAmount;
            $this->commissionSum += $commission;
            $this->commissionCtSum += $commissionCt;
            $this->balanceSum += $balance;
            return [
                $key + 1,
                $item->vendor->shopOrder->invoice_id,
                Carbon::parse($item->vendor->shopOrder->order_date)->format('M d Y h:i a'),
                $shop->name,
                $item->product_name,
                $item->amount,
                $item->vendor_price,
                implode(',', array_map(function ($n) {
                    return $n['value'];
                }, $item->variant)),
                $item->quantity,
                $amount,
                $item->vendor->shopOrder->order_status != 'cancelled' && $item->tax ? $item->tax : '0',
                $item->vendor->shopOrder->order_status != 'cancelled' && $item->discount ? $item->discount : '0',
                $item->vendor->shopOrder->order_status != 'cancelled' && $item->promo ? $item->promo : '0',
                $totalAmount,
                $commission ? $commission : '0',
                $commissionCt ? $commissionCt : '0',
                round($balance),
                $item->vendor->shopOrder->payment_mode,
                $item->vendor->shopOrder->payment_status,
                $item->vendor->shopOrder->order_status,
                $item->vendor->shopOrder->special_instruction,
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
                'shop',
                'product_name',
                'price',
                'vendor_price',
                'variant',
                'quantity',
                'revenue',
                'commercial tax',
                'discount',
                'promo discount',
                "total amount\n(tax inclusive)",
                'commission',
                'ct on commision',
                'balance',
                'payment mode',
                'payment status',
                'order status',
                'special instructions',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 12,
            'C' => 20,
            'D' => 20,
            'E' => 30,
            'F' => 10,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 17,
            'N' => 20,
            'O' => 15,
            'P' => 15,
            'Q' => 15,
            'R' => 15,
            'S' => 15,
            'T' => 15,
            'U' => 30,
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
            'H' => ['alignment' => ['horizontal' => 'center']],
            'R' => ['alignment' => ['horizontal' => 'center']],
            'S' => ['alignment' => ['horizontal' => 'center']],
            'T' => ['alignment' => ['horizontal' => 'center']],
            'U' => ['alignment' => ['horizontal' => 'center']],
            2 => ['alignment' => ['horizontal' => 'left']],
            3 => ['alignment' => ['horizontal' => 'left']],
            4 => ['alignment' => ['horizontal' => 'left']],
            6 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0',
            'G' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
            'M' => '#,##0',
            'N' => '#,##0',
            'O' => '#,##0',
            'P' => '#,##0',
            'Q' => '#,##0',
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
                $event->sheet->getStyle(sprintf('N%d:Q%d', $lastRow - 1, $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('N%d:Q%d', $lastRow, $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('Q%d', $lastRow))->getFont()->setBold(true);

                $event->sheet->setCellValue(sprintf('J%d', $lastRow), $this->amountSum);
                $event->sheet->setCellValue(sprintf('N%d', $lastRow), $this->totalAmountSum);
                $event->sheet->setCellValue(sprintf('O%d', $lastRow), $this->commissionSum);
                $event->sheet->setCellValue(sprintf('P%d', $lastRow), $this->commissionCtSum);
                $event->sheet->setCellValue(sprintf('Q%d', $lastRow), $this->balanceSum);

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
        return 'Product Sales report';
    }
}