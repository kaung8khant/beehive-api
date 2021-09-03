<?php

namespace App\Exports\Sales;

use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopOrderVendor;
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

class ShopSalesExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $from;
    protected $to;

    protected $result;
    protected $amountSum;
    protected $totalAmountSum;
    protected $commissionSum;
    protected $commissionCtSum;
    protected $balanceSum;
    protected $promoDiscount;
    protected $key;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        $shopOrderVendors = ShopOrderVendor::whereHas('shopOrder', function ($query) {
            $query->whereBetween('order_date', [$this->from, $this->to])->where('order_status', '!=', 'cancelled');
        })->get()->groupBy('shop_id');
        $shopOrders = ShopOrder::whereBetween('order_date', [$this->from, $this->to])
        ->where('order_status', '!=', 'cancelled')
        ->get();

        foreach ($shopOrders as $k => $order) {
            $this->promoDiscount+=$order->promocode_amount;
        }

        $this->result = $shopOrderVendors->map(function ($group) {
            foreach ($group as $vendor) {
                $shop = Shop::where('id', $vendor->shop_id)->first();

                $amount = $vendor->shopOrder->order_status == 'cancelled' ? '0' : $vendor->amount;
                $commission = $vendor->shopOrder->order_status == 'cancelled' ? '0' : $vendor->commission;
                $commissionCt = $commission * 0.05;
                $totalAmount =  $vendor->shopOrder->order_status == 'cancelled' ? '0' : $vendor->total_amount;
                $balance = $totalAmount - $commissionCt;

                $this->amountSum += $amount;
                $this->totalAmountSum += $totalAmount;
                $this->commissionSum += $commission;
                $this->commissionCtSum += $commissionCt;
                $this->balanceSum += $balance;
            }
            return [
                $this->key += 1,
                $shop->name,
                $amount,
                $vendor->tax ? $vendor->tax : '0',
                $vendor->discount ? $vendor->discount : '0',
                $totalAmount,
                $commission ? $commission : '0',
                $commissionCt ? $commissionCt : '0',
                round($balance),
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
                'shop',
                'revenue',
                'commercial tax',
                'discount',
                "total amount\n(tax inclusive)",
                'commission',
                'ct on commision',
                'balance',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 30,
            'C' => 20,
            'D' => 30,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(79);

        return [
            'A' => ['alignment' => ['horizontal' => 'center']],
            'B' => ['alignment' => ['horizontal' => 'center']],
            2 => ['alignment' => ['horizontal' => 'left']],
            3 => ['alignment' => ['horizontal' => 'left']],
            4 => ['alignment' => ['horizontal' => 'left']],
            6 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0',
            'D' => '#,##0',
            'E' => '#,##0',
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
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
                $lastRow = count($this->result) + 8 + 1;

                $event->sheet->getStyle(sprintf('C%d', $lastRow - 3))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('C%d', $lastRow - 2))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('E%d:I%d', $lastRow - 3, $lastRow - 3))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('E%d:I%d', $lastRow - 2, $lastRow - 2))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('E%d:I%d', $lastRow - 1, $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('E%d:I%d', $lastRow, $lastRow))->getBorders()->getBottom()->setBorderStyle('thin');

                $event->sheet->getStyle(sprintf('I%d', $lastRow - 2))->getFont()->setBold(true);
                $event->sheet->getStyle(sprintf('E%d', $lastRow-1))->getFont()->setBold(true);
                $event->sheet->getStyle(sprintf('E%d', $lastRow))->getFont()->setBold(true);
                $event->sheet->getStyle(sprintf('E%d', $lastRow -1))->getAlignment()->setHorizontal('center');
                $event->sheet->getStyle(sprintf('E%d', $lastRow))->getAlignment()->setHorizontal('center');


                $event->sheet->setCellValue(sprintf('C%d', $lastRow-2), $this->amountSum);
                $event->sheet->setCellValue(sprintf('F%d', $lastRow-2), $this->totalAmountSum);
                $event->sheet->setCellValue(sprintf('G%d', $lastRow-2), $this->commissionSum);
                $event->sheet->setCellValue(sprintf('H%d', $lastRow-2), $this->commissionCtSum);
                $event->sheet->setCellValue(sprintf('I%d', $lastRow-2), $this->balanceSum);

                $event->sheet->setCellValue(sprintf('E%d', $lastRow - 1), 'Promo Discount');
                $event->sheet->setCellValue(sprintf('F%d', $lastRow - 1), $this->promoDiscount);
                $event->sheet->setCellValue(sprintf('I%d', $lastRow - 1), $this->promoDiscount);
                $event->sheet->setCellValue(sprintf('E%d', $lastRow), 'Net Amount');
                $event->sheet->setCellValue(sprintf('F%d', $lastRow), $this->totalAmountSum - $this->promoDiscount);
                $event->sheet->setCellValue(sprintf('I%d', $lastRow), $this->balanceSum - $this->promoDiscount);


                $event->sheet->getStyle($lastRow - 2)->getNumberFormat()->setFormatCode('#,##0');
                $event->sheet->getStyle(sprintf('F%d', $lastRow - 2))->getNumberFormat()->setFormatCode('#,##0');
                $event->sheet->getStyle(sprintf('F%d', $lastRow - 1))->getNumberFormat()->setFormatCode('#,##0');

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
        return 'Shop Sales report';
    }
}
