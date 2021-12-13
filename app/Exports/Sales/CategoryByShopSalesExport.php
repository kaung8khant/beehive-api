<?php

namespace App\Exports\Sales;

use App\Models\Shop;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopOrderVendor;
use Carbon\Carbon;
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

class CategoryByShopSalesExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithStyles, WithTitle
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
            $query->whereBetween('order_date', [$this->from, $this->to]);
        })->where('shop_id', $shop->id)->get();

        $groups = collect($shopOrderItems)->groupBy(function ($item, $key) {
            return $item->product->shopCategory->id;
        });

        $this->result=$groups->map(function ($group) {
            $amount = 0;
            $commercialTax = 0;
            $totalAmount = 0;
            $commission = 0;
            $commissionCt = 0;
            $quantity = 0;
            foreach ($group as $item) {
                $amount += $item->amount * $item->quantity;
                $commission +=  $item->commission;
                $commissionCt += $commission * 0.05;
                $totalAmount += $item->total_amount;
                $balance = $totalAmount - $commissionCt;
                $commercialTax += $item->tax ? $item->tax * $item->quantity : 0;
                $quantity += $item->quantity;
            }
            $this->key += 1;
            $this->amountSum += $amount;
            $this->totalAmountSum += $totalAmount;
            $this->commissionSum += $commission;
            $this->commissionCtSum += $commissionCt;
            $this->balanceSum += $balance;
            return [
                $this->key,
                $group[0]->product->shopCategory->name,
                $quantity,
                $amount,
                $commercialTax ? $commercialTax : '0',
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
                'category',
                'quantity',
                'revenue',
                'commercial tax',
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
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
            'I' => 20,
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
                $lastRow = count($this->result) + 6 + 1;

                $event->sheet->getStyle(sprintf('D%d', $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('D%d', $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('F%d:I%d', $lastRow - 1, $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('F%d:I%d', $lastRow, $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('I%d', $lastRow))->getFont()->setBold(true);

                $event->sheet->setCellValue(sprintf('D%d', $lastRow), $this->amountSum);
                $event->sheet->setCellValue(sprintf('F%d', $lastRow), $this->totalAmountSum);
                $event->sheet->setCellValue(sprintf('G%d', $lastRow), $this->commissionSum);
                $event->sheet->setCellValue(sprintf('H%d', $lastRow), $this->commissionCtSum);
                $event->sheet->setCellValue(sprintf('I%d', $lastRow), $this->balanceSum);

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
        return 'Category By Shop Sales report';
    }
}
