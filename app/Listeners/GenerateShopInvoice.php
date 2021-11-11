<?php

namespace App\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use niklasravnsborg\LaravelPdf\Facades\Pdf as PDF;

class GenerateShopInvoice
{
    const PATH = 'pdf/shops/';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $shopOrder = $event->order;
        $vendors = $shopOrder->vendors;
        $contact = $shopOrder->contact;
        $date = Carbon::parse($shopOrder->order_date)->format('d M Y');

        $fileName = $shopOrder->slug . '-' . $shopOrder->invoice_id . '.pdf';

        $pdf = PDF::loadView('shop-invoice', compact('shopOrder', 'vendors', 'contact', 'date'))->output();
        Storage::put(self::PATH . $fileName, $pdf);
    }
}
