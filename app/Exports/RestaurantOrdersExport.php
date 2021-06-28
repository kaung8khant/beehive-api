<?php

namespace App\Exports;

use App\Models\Restaurant;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderContact;
use App\Models\Township;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantOrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query()
    {
        return RestaurantOrder::query();
    }

    /**
     * @var RestaurantOrder $restaurantOrder
     */
    public function map($restaurantOrder): array
    {
        $contact = RestaurantOrderContact::where('restaurant_order_id', $restaurantOrder->id);
        $floor = $contact->value('floor') ? ', (' . $contact->value('floor') . ') ,' : ',';
        $address = 'No.' . $contact->value('house_number') . $floor . $contact->value('street_name');
        return [
            $restaurantOrder->slug,
            $restaurantOrder->invoice_id,
            Carbon::parse($restaurantOrder->order_date)->format('M d Y h:i a'),
            Restaurant::where('id', $restaurantOrder->restaurant_id)->value('name'),
            RestaurantBranch::where('id', $restaurantOrder->restaurant_branch_id)->value('name'),
            RestaurantBranch::where('id', $restaurantOrder->restaurant_branch_id)->value('contact_number'),
            $contact->value('customer_name'),
            $contact->value('phone_number'),
            $address,
            $restaurantOrder->total_amount,
            $restaurantOrder->payment_mode,
            $restaurantOrder->delivery_mode,
            $restaurantOrder->special_instruction,
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
