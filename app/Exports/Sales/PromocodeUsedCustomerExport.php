<?php

namespace App\Exports\Sales;

use App\Models\Customer;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
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

class PromocodeUsedCustomerExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $param;
    protected $from;
    protected $to;

    protected $result;
    protected $totalAmountSum;
    protected $totalPromoDiscountSum;
    protected $totalFrequency;
    protected $key;

    public function __construct($param, $from, $to)
    {
        $this->param = $param;
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        $promocode = Promocode::where('slug', $this->param)->first();
        $shopOrders = ShopOrder::where('promocode_id', $promocode->id)->where('order_status', '!=', 'cancelled')->whereBetween('order_date', [$this->from, $this->to])
            ->get();
        $restaurantOrders = RestaurantOrder::where('promocode_id', $promocode->id)->whereBetween('order_date', [$this->from, $this->to])->where('order_status', '!=', 'cancelled')
            ->get();
        $orderList = collect($shopOrders)->merge($restaurantOrders)->groupBy('customer_id');
        $this->result = $orderList->map(function ($orders) {
            $totalAmount = 0;
            $totalPromoDiscount = 0;
            foreach ($orders as $order) {
                $totalAmount +=  ($order->tax+$order->amount);
                $totalPromoDiscount += $order->promocode_amount ? $order->promocode_amount : '0';
            }
            $this->key += 1;
            $customer = Customer::where('id', $orders[0]->customer_id)->first();
            $this->totalAmountSum += $totalAmount;
            $this->totalPromoDiscountSum += $totalPromoDiscount;
            $this->totalFrequency += $orders->count();
            return [
                $this->key,
                $customer->slug,
                $customer->name,
                $customer->email,
                $customer->phone_number,
                $totalAmount,
                $totalPromoDiscount,
                $orders->count(),
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
                'customer id',
                'name',
                'email',
                'phone number',
                "total amount\n(tax inclusive)",
                'total promo discount',
                'frequency',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 20,
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
            'H' => '#,##0',
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

                $event->sheet->getStyle(sprintf('F%d:H%d', $lastRow - 1, $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('F%d:H%d', $lastRow, $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('H%d', $lastRow))->getFont()->setBold(true);

                $event->sheet->setCellValue(sprintf('F%d', $lastRow), $this->totalAmountSum);
                $event->sheet->setCellValue(sprintf('G%d', $lastRow), $this->totalPromoDiscountSum);
                $event->sheet->setCellValue(sprintf('H%d', $lastRow), $this->totalFrequency);

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
        return 'Promocode Used Customer report';
    }
}
