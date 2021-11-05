<?php

namespace App\Exports\Sales;

use App\Models\ShopOrder;
use App\Models\ShopOrderStatus;
use App\Models\ShopOrderVendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShopInvoiceSalesExport extends DefaultValueBinder implements FromCollection, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithDrawings, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected $from;
    protected $to;
    protected $filterBy;

    protected $result;
    protected $amountSum;
    protected $totalAmountSum;
    protected $commissionSum;
    protected $commissionCtSum;
    protected $balanceSum;

    public function __construct($from, $to, $filterBy)
    {
        $this->from = $from;
        $this->to = $to;
        $this->filterBy = $filterBy;
    }

    public function collection()
    {
        if ($this->filterBy === 'orderDate') {
            $shopOrders = ShopOrder::with('contact', 'vendors')
            ->whereBetween('order_date', [$this->from, $this->to])
            ->orderBy('id')
            ->get();
        } else {
            $shopOrders = ShopOrder::with('contact', 'vendors')
                ->whereHas('vendors', function ($query) {
                    $query->whereHas('shopOrderStatuses', function ($q) {
                        $q->whereBetween('created_at', [$this->from, $this->to])->where('status', '=', 'delivered')->latest();
                    });
                })
                ->orderBy('id')
                ->get();
        }

        $this->result = $shopOrders->map(function ($order, $key) {
            $amount = $order->order_status == 'cancelled' ? '0' : $order->amount;
            $commission = $order->order_status == 'cancelled' ? '0' : $order->commission;
            $commissionCt = $order->commission * 0.05;
            $totalAmount = $order->order_status == 'cancelled' ? '0' : $order->total_amount;
            $balance = $totalAmount - $commissionCt;

            $this->amountSum += $amount;
            $this->totalAmountSum += $totalAmount;
            $this->commissionSum += $commission;
            $this->commissionCtSum += $commissionCt;
            $this->balanceSum += $balance;
            $vendorIds = ShopOrderVendor::whereHas('shopOrder', function ($query) use ($order) {
                $query->where('shop_order_id', $order->id);
            })->pluck('id')->toArray();
            $orderStatus = ShopOrderStatus::where('status', 'delivered')
            ->whereHas('vendor', function ($query) use ($vendorIds) {
                $query->whereIn('id', $vendorIds);
            })->latest('created_at')->first();
            $invoiceOrderStatus = ShopOrderStatus::where('status', 'pickUp')
            ->whereHas('vendor', function ($query) use ($vendorIds) {
                $query->whereIn('id', $vendorIds);
            })->latest('created_at')->first();

            if ($this->filterBy === 'deliveredDate') {
                $orderStatus = ShopOrderStatus::where('status', 'delivered')->whereBetween('created_at', array($this->from, $this->to))->whereHas('vendor', function ($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })->latest('created_at')->first();
            }
            if ($this->filterBy === 'invoiceDate') {
                $invoiceOrderStatus = ShopOrderStatus::where('status', 'pickUp')->whereBetween('created_at', array($this->from, $this->to))->whereHas('vendor', function ($query) use ($vendorIds) {
                    $query->whereIn('id', $vendorIds);
                })->latest('created_at')->first();
            }
            return [
                $key + 1,
                $order->invoice_id,
                Carbon::parse($order->order_date)->format('M d Y h:i a'),
                $orderStatus ? Carbon::parse($orderStatus->created_at)->format('M d Y h:i a') : null,
                $invoiceOrderStatus ? Carbon::parse($invoiceOrderStatus->created_at)->format('M d Y h:i a') : null,
                $amount,
                $order->order_status != 'cancelled' && $order->tax ? $order->tax : '0',
                $order->order_status != 'cancelled' && $order->discount ? $order->discount : '0',
                $order->order_status != 'cancelled' && $order->promocode_amount ? $order->promocode_amount : '0',
                $totalAmount,
                $commission ? $commission : '0',
                $commissionCt ? $commissionCt : '0',
                round($balance),
                $order->payment_mode,
                $order->payment_status,
                $order->payment_reference,
                $order->order_status,
                $order->special_instruction,
                $order->contact->customer_name,
                $order->contact->phone_number,
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
                'delivered date',
                'invoice date',
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
            'B' => 12,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 17,
            'N' => 20,
            'O' => 30,
            'P' => 25,
            'Q' => 20,
            'R' => 20,
            'S' => 20,
            'T' => 20,
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
            'N' => ['alignment' => ['horizontal' => 'center']],
            'O' => ['alignment' => ['horizontal' => 'center']],
            'P' => ['alignment' => ['horizontal' => 'center']],
            'Q' => ['alignment' => ['horizontal' => 'center']],
            'R' => ['alignment' => ['horizontal' => 'center']],
            'S' => ['alignment' => ['horizontal' => 'right']],
            'T' => ['alignment' => ['horizontal' => 'right']],
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
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
            'M' => '#,##0',
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

                $event->sheet->getStyle(sprintf('F%d', $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('F%d', $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('J%d:M%d', $lastRow - 1, $lastRow - 1))->getBorders()->getBottom()->setBorderStyle('thin');
                $event->sheet->getStyle(sprintf('J%d:M%d', $lastRow, $lastRow))->getBorders()->getBottom()->setBorderStyle('double');
                $event->sheet->getStyle(sprintf('M%d', $lastRow))->getFont()->setBold(true);

                $event->sheet->setCellValue(sprintf('F%d', $lastRow), $this->amountSum);
                $event->sheet->setCellValue(sprintf('J%d', $lastRow), $this->totalAmountSum);
                $event->sheet->setCellValue(sprintf('K%d', $lastRow), $this->commissionSum);
                $event->sheet->setCellValue(sprintf('L%d', $lastRow), $this->commissionCtSum);
                $event->sheet->setCellValue(sprintf('M%d', $lastRow), $this->balanceSum);

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
        return 'Shop Invoice Sales report';
    }

    public function bindValue(Cell $cell, $value)
    {
        $validator = Validator::make(['phone' => $value], [
            'phone' => 'required|phone:MM',
        ]);

        if (!$validator->fails()) {
            $cell->setValueExplicit($value, 's');
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
