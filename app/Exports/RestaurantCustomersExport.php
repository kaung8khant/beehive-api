<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\Shop;
use App\Models\ShopOrder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RestaurantCustomersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    use Exportable;

    public function __construct(string $params)
    {
        $this->params = $params;
        ini_set('memory_limit', '256M');
    }

    public function query()
    {
        $branch = RestaurantBranch::where('slug', $this->params)->firstOrFail();

        $orderList = RestaurantOrder::where('restaurant_branch_id', $branch->id)->pluck('id');

        $customers = Customer::query()->whereIn('id', $orderList);
        return $customers;
    }
    /**
     * @var Customer $restaurantCustomer
     */
    public function map($restaurantCustomer): array
    {
        return [
            $restaurantCustomer->slug,
            $restaurantCustomer->name,
            $restaurantCustomer->email,
            $restaurantCustomer->phone_number,
            $restaurantCustomer->gender,
        ];
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone_number',
            'gender',
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
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 20,
            'D' => 50,
            'E' => 15,
        ];
    }
}
