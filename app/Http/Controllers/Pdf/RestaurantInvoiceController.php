<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use niklasravnsborg\LaravelPdf\Facades\Pdf as PDF;

class RestaurantInvoiceController extends Controller
{
    public function generateInvoice($slug)
    {
        $restaurantOrder = RestaurantOrder::where('slug', $slug)->firstOrFail();
        $branchInfo = $restaurantOrder->restaurant_branch_info;
        $restaurantOrderItems = $restaurantOrder->restaurantOrderItems;
        $restaurantOrderContact = $restaurantOrder->restaurantOrderContact;
        $date = Carbon::parse($restaurantOrder->order_date)->format('d M Y');

        $fileName = $restaurantOrder->slug . '-' . $restaurantOrder->invoice_id . '.pdf';

        $pdf = PDF::loadView('restaurant-invoice', compact('restaurantOrder', 'branchInfo', 'restaurantOrderItems', 'restaurantOrderContact', 'date'));
        return $pdf->download($fileName);
    }
}
