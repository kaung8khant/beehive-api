<?php

namespace App\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use niklasravnsborg\LaravelPdf\Facades\Pdf as PDF;

class GenerateRestaurantInvoice
{
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
        $restaurantOrder = $event->order;
        $branchInfo = $restaurantOrder->restaurant_branch_info;
        $restaurantOrderItems = $restaurantOrder->restaurantOrderItems;
        $restaurantOrderContact = $restaurantOrder->restaurantOrderContact;
        $date = Carbon::parse($restaurantOrder->order_date)->format('d M Y');

        $fileName = $restaurantOrder->slug . '-' . $restaurantOrder->invoice_id . '.pdf';

        $pdf = PDF::loadView('restaurant-invoice', compact('restaurantOrder', 'branchInfo', 'restaurantOrderItems', 'restaurantOrderContact', 'date'));

        Storage::put('pdf/restaurants/' . $fileName, $pdf);
    }
}
