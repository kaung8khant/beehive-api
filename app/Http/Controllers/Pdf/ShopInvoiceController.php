<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;

class ShopInvoiceController extends Controller
{
    public function generateInvoice($slug)
    {
        $shopOrder = ShopOrder::where('slug', $slug)->firstOrFail();
        $vendors = $shopOrder->vendors;
        $contact = $shopOrder->contact;
        $date = Carbon::parse($shopOrder->order_date)->format('d M Y');

        $fileName = $shopOrder->slug . '-' . $shopOrder->invoice_id . '.pdf';

        $pdf = PDF::loadView('shop-invoice', compact('shopOrder', 'vendors', 'contact', 'date'))->setPaper('a4');
        return $pdf->download($fileName);
    }
}
