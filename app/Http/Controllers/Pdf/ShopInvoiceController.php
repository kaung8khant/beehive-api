<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use Carbon\Carbon;
use niklasravnsborg\LaravelPdf\Facades\Pdf as PDF;

class ShopInvoiceController extends Controller
{
    public function generateInvoice($slug)
    {
        $shopOrder = ShopOrder::where('slug', $slug)->firstOrFail();
        $vendors = $shopOrder->vendors;
        $contact = $shopOrder->contact;
        $date = Carbon::parse($shopOrder->order_date)->format('d M Y');

        $fileName = $shopOrder->slug . '-' . $shopOrder->invoice_id . '.pdf';

        $pdf = PDF::loadView('shop-invoice', compact('shopOrder', 'vendors', 'contact', 'date'));
        return $pdf->download($fileName);
    }
}
