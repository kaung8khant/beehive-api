<?php

namespace App\Exports\Sales;

use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderStatus;
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

class RestaurantBranchSalesExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithDrawings, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $param;
    protected $from;
    protected $to;
    protected $filterBy;

    protected $result;
    protected $amountSum;
    protected $totalAmountSum;
    protected $commissionSum;
    protected $commissionCtSum;
    protected $balanceSum;

    public function __construct($param, $from, $to, $filterBy)
    {
        $this->param = $param;
        $this->from = $from;
        $this->to = $to;
        $this->filterBy = $filterBy;
    }

    public function collection()
    {
        $restaurantBranch = RestaurantBranch::with('restaurant')->where('slug', $this->param)->first();

        $restaurantOrders = RestaurantOrder::with('restaurantOrderContact')
            ->where('restaurant_branch_id', $restaurantBranch->id)
            ->orderBy('restaurant_id')
            ->orderBy('restaurant_branch_id')
            ->orderBy('id');
        if ($this->filterBy === 'orderDate') {
            $restaurantOrders =  $restaurantOrders
                ->whereBetween('order_date', [$this->from, $this->to])
                ->get();
        } elseif ($this->filterBy === 'deliveredDate') {
            $restaurantOrders =  $restaurantOrders
                ->whereHas('restaurantOrderStatuses', function ($query) {
                    $query->whereBetween('created_at', [$this->from, $this->to])->where('status', '=', 'delivered')->orderBy('created_at', 'desc');
                })->get();
        } else {
            $restaurantOrders =  $restaurantOrders
            ->whereHas('restaurantOrderStatuses', function ($query) {
                $query->whereBetween('created_at', [$this->from, $this->to])->where('status', '=', 'pickUp')->orderBy('created_at', 'desc');
            })->get();
        }

        $this->result = $restaurantOrders->map(function ($order, $key) use ($restaurantBranch) {
            $amount = $order->order_status == 'cancelled' ? '0' : $order->amount;
            $commission = $amount * $restaurantBranch->restaurant->commission * 0.01;
            $commissionCt = $commission * 0.05;
            $totalAmount = $order->order_status == 'cancelled' ? '0' : $order->total_amount;
            $balance = $totalAmount - $commissionCt;

            $this->amountSum += $amount;
            $this->totalAmountSum += $totalAmount;
            $this->commissionSum += $commission;
            $this->commissionCtSum += $commissionCt;
            $this->balanceSum += $balance;

            $orderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'delivered')->orderBy('created_at', 'desc')->first();
            $invoiceOrderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'pickUp')->orderBy('created_at', 'desc')->first();

            if ($this->filterBy === 'deliveredDate') {
                $orderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'delivered')->whereBetween('created_at', array($this->from, $this->to))->orderBy('created_at', 'desc')->first();
            }
            if ($this->filterBy === 'invoiceDate') {
                $invoiceOrderStatus = RestaurantOrderStatus::where('restaurant_order_id', $order->id)->where('status', 'pickUp')->whereBetween('created_at', array($this->from, $this->to))->orderBy('created_at', 'desc')->first();
            }
            return [
                $key + 1,
                $order->order_no,
                $order->invoice_no,
                Carbon::parse($order->order_date)->format('M d Y h:i a'),
                $invoiceOrderStatus ? Carbon::parse($invoiceOrderStatus->created_at)->format('M d Y h:i a') : null,
                $orderStatus ? Carbon::parse($orderStatus->created_at)->format('M d Y h:i a') : null,
                $restaurantBranch->restaurant->name,
                $restaurantBranch->name,
                $amount,
                $order->order_status != 'cancelled' && $order->tax ? $order->tax : '0',
                $order->order_status != 'cancelled' && $order->discount ? $order->discount : '0',
                $order->order_status != 'cancelled' && $order->promocode_amount ? $order->promocode_amount : '0',
                $order->delivery_fee != 'cancelled' && $order->delivery_fee ? $order->delivery_fee : '0',
                $totalAmount,
                $restaurantBranch->restaurant->commission ? $restaurantBranch->restaurant->commission : '0',
                $commission ? $commission : '0',
                $commissionCt ? $commissionCt : '0',
                round($balance),
                $order->payment_mode,
                $order->payment_status,
                $order->payment_reference,
                $order->order_status,
                $order->order_type,
                $order->special_instruction,
                $order->restaurantOrderContact->customer_name,
                $order->restaurantOrderContact->phone_number,
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
                'order no',
                'invoice no',
                'order date',
                'invoice date',
                'deliverd date',
                'restaurant',
                'branch',
                'revenue',
                'commercial tax',
                'discount',
                'promo discount',
                'delivery fee',
                "total amount\n(tax inclusive)",
                'commission rate',
                'commission',
                'ct on commision',
                'balance',
                'payment mode',
                'payment status',
                'payment reference',
                'order status',
                "type\n(deli/self pick up)",
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
            'B' => 15,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 30,
            'G' => 30,
            'H' => 30,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 20,
            'M' => 17,
            'N' => 15,
            'O' => 17,
            'P' => 17,
            'Q' => 20,
            'R' => 20,
            'S' => 20,
            'T' => 20,
            'U' => 30,
            'V' => 30,
            'W' => 20,
            'X' => 20,
            'Y' => 20,
            'Z' => 20,
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
            'G' => ['alignment' => ['horizontal' => 'center']],
            'H' => ['alignment' => ['horizontal' => 'center']],
            'S' => ['alignment' => ['horizontal' => 'center']],
            'T' => ['alignment' => ['horizontal' => 'center']],
            'U' => ['alignment' => ['horizontal' => 'center', 'wrapText' => true]],
            'V' => ['alignment' => ['horizontal' => 'center']],
            'W' => ['alignment' => ['horizontal' => 'center']],
            'X' => ['alignment' => ['horizontal' => 'center', 'wrapText' => true]],
            'Y' => ['alignment' => ['horizontal' => 'center']],
            'Z' => ['alignment' => ['horizontal' => 'center']],
            2 => ['alignment' => ['horizontal' => 'left']],
            3 => ['alignment' => ['horizontal' => 'left']],
            4 => ['alignment' => ['horizontal' => 'left']],
            6 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
            'M' => '#,##0',
            'N' => '#,##0',
            'O' => '#,##0',
            'P' => '#,##0',
            'Q' => '#,##0',
            'R' => '#,##0',
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

                $event->sheet->getStyle(sprintf('I%d', $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('I%d', $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('N%d:R%d', $lastRow - 1, $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('N%d:R%d', $lastRow, $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('R%d', $lastRow))->getFont()->setBold(true);

                $event->sheet->setCellValue(sprintf('I%d', $lastRow), $this->amountSum);
                $event->sheet->setCellValue(sprintf('N%d', $lastRow), $this->totalAmountSum);
                $event->sheet->setCellValue(sprintf('P%d', $lastRow), $this->commissionSum);
                $event->sheet->setCellValue(sprintf('Q%d', $lastRow), $this->commissionCtSum);
                $event->sheet->setCellValue(sprintf('R%d', $lastRow), $this->balanceSum);

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
        return 'Sales report';
    }
}
