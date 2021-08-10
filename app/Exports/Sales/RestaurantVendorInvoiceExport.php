<?php

namespace App\Exports\Sales;

use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantVendorInvoiceExport implements WithColumnFormatting, WithColumnWidths, WithDrawings, WithHeadings, WithStyles, WithTitle
{
    protected $param;
    protected $from;
    protected $to;

    public function __construct($param, $from, $to)
    {
        $this->param = $param;
        $this->from = $from;
        $this->to = $to;
    }

    public function headings(): array
    {
        $invoiceDate = Carbon::now()->format('d M Y');
        $from = Carbon::parse($this->from)->format('d M Y');
        $to = Carbon::parse($this->to)->format('d M Y');

        $restaurant = Restaurant::where('slug', $this->param)->first();
        $restaurantOrders = RestaurantOrder::where('restaurant_id', $restaurant->id)
            ->where('order_status', '<>', 'cancelled')
            ->whereBetween('order_date', [$this->from, $this->to])
            ->get();

        $commissionSum = 0;
        $commissionCtSum = 0;

        foreach ($restaurantOrders as $order) {
            $commission = $order->amount * $restaurant->commission * 0.01;
            $commissionCt = $commission * 0.05;

            $commissionSum += $commission;
            $commissionCtSum += $commissionCt;
        }

        $month = Carbon::parse($this->to)->format('F');

        $invoiceNo = $this->param . '-' . Carbon::parse($this->from)->format('d') . '' . Carbon::parse($this->to)->format('d') . '' . Carbon::parse($this->to)->format('m') . '' . Carbon::parse($this->to)->format('y');

        return [
            ['', '', '', "\nBeehive\nManaged by Hive Innovation Co. Ltd\n\nNo. 485, Corner of Pyay rd & Nanatdaw rd, Kamaryut Tsp, Yangon, Myanmar.\n"],
            ['Invoice Date:', $invoiceDate, '', 'Tel: 09- 255 114 519'],
            ['Bill Report:', $from . ' to ' . $to, '', 'Email: Info@hiveinnovate.com'],
            ['Vendor ID:', $this->param],
            ['Billing:', $restaurant->name],
            ['Invoice No:', $invoiceNo],
            [],
            [],
            ['No.', 'Item Description', 'Quantity', 'Total Amount (MMK)'],
            [1, 'Vendor Revenue (inc CT)', number_format($restaurantOrders->count()), $restaurantOrders->sum('total_amount')],
            [2, "Invoice for {$month} commission", '', $commissionSum ? $commissionSum : '0'],
            [3, 'CT on commission', '', $commissionCtSum ? $commissionCtSum : '0'],
            ['', 'Payable to Vendor', '', $restaurantOrders->sum('total_amount') - $commissionSum - $commissionCtSum],
            [],
            [],
            ['Vendor Bank Info:'],
            ['Beneficiary:'],
            ['Bank Name:'],
            ['Acc No:'],
            [],
            ['* Commercial Tax are not collected for the  sales of ' . $month . '.'],
            ['* Commision to Beehive will be waived for the month of ' . $month . '.'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 15,
            'D' => 35,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(79);

        $sheet->getStyle('D1:D3')->getFont()->getColor()->setRGB('808080');
        $sheet->getStyle('A21:A22')->getFont()->getColor()->setRGB('808080');
        $sheet->getStyle('A9:D9')->getBorders()->getOutline()->setBorderStyle('thin');
        $sheet->getStyle('A12:D12')->getBorders()->getBottom()->setBorderStyle('thin');
        $sheet->getStyle('A10:A12')->getBorders()->getLeft()->setBorderStyle('thin');
        $sheet->getStyle('D10:D12')->getBorders()->getRight()->setBorderStyle('thin');
        $sheet->getStyle('D13')->getBorders()->getBottom()->setBorderStyle('double');

        return [
            'A2:B6' => ['font' => ['bold' => true]],
            'D1:D3' => ['font' => ['name' => 'Arial', 'size' => 8], 'alignment' => ['wrapText' => true]],
            'D1' => ['font' => ['bold' => true]],
            'B13' => ['font' => ['bold' => true]],
            'B10:B13' => ['alignment' => ['horizontal' => 'center']],
            'C10:C12' => ['alignment' => ['horizontal' => 'center']],
            'A21:A22' => ['font' => ['size' => 10]],
            9 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D10:D13' => '#,##0',
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

    public function title(): string
    {
        return 'Invoice';
    }
}
