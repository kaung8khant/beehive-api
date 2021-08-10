<?php

namespace App\Exports;

use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderContact;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantBranchOrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(string $params, $from, $to)
    {
        $this->params = $params;
        $this->from = $from;
        $this->to = $to;
    }

    public function query()
    {
        $restaurantBranch = RestaurantBranch::where('slug', $this->params)->firstOrFail();

        return RestaurantOrder::query()->where('restaurant_branch_id', $restaurantBranch->id)
            ->whereBetween('order_date', array($this->from, $this->to));
    }

    /**
     * @var RestaurantOrder $restaurantBranchOrder
     */
    public function map($restaurantBranchOrder): array
    {
        $contact = RestaurantOrderContact::where('restaurant_order_id', $restaurantBranchOrder->id);
        $floor = $contact->value('floor') ? ', (' . $contact->value('floor') . ') ,' : ',';
        $address = 'No.' . $contact->value('house_number') . $floor . $contact->value('street_name');
        return [
            $restaurantBranchOrder->slug,
            $restaurantBranchOrder->invoice_id,
            Carbon::parse($restaurantBranchOrder->order_date)->format('M d Y  h:i a'),
            Restaurant::where('id', $restaurantBranchOrder->restaurant_id)->value('name'),
            RestaurantBranch::where('id', $restaurantBranchOrder->restaurant_branch_id)->value('name'),
            RestaurantBranch::where('id', $restaurantBranchOrder->restaurant_branch_id)->value('contact_number'),
            $contact->value('customer_name'),
            $contact->value('phone_number'),
            $address,
            $restaurantBranchOrder->total_amount,
            $restaurantBranchOrder->payment_mode,
            $restaurantBranchOrder->delivery_mode,
            $restaurantBranchOrder->special_instruction,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'invoice_id',
            'order_date',
            'restaurant',
            'branch',
            'branch_contact_number',
            'customer',
            'customer_phone_number',
            'address',
            'amount',
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 50,
            'C' => 20,
            'D' => 50,
            'E' => 20,
            'F' => 10,
            'G' => 20,
            'H' => 15,
            'I' => 70,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 15,
        ];
    }
}
