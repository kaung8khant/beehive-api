<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use Barryvdh\DomPDF\Facade as PDF;

class RestaurantInvoiceController extends Controller
{
    public function generateInvoice($slug)
    {
        $restaurantOrder = RestaurantOrder::where('slug', $slug)->firstOrFail();
        $branchInfo = $restaurantOrder->restaurant_branch_info;

        $fileName = $restaurantOrder->slug . '-' . $restaurantOrder->invoice_id . '.pdf';

        // return $restaurantOrder->restaurant_branch_info;

        $pdf = PDF::loadView('restaurant-invoice', compact('restaurantOrder', 'branchInfo'))->setPaper('a4');
        return $pdf->download($fileName);
    }
}
