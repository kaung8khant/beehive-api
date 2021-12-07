<?php

namespace App\Http\Controllers\Pdf;

use App\Events\RestaurantOrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class RestaurantInvoiceController extends Controller
{
    const PATH = 'pdf/restaurants/';

    // public function generateInvoice($slug)
    // {
    //     $restaurantOrder = RestaurantOrder::where('slug', $slug)->firstOrFail();
    //     event(new RestaurantOrderUpdated($restaurantOrder));

    //     $fileName = $restaurantOrder->slug . '-' . $restaurantOrder->invoice_id . '.pdf';
    //     return Storage::download(self::PATH . $fileName);
    // }


    public function generateInvoice($slug)
    {
        $restaurantOrder = RestaurantOrder::where('slug', $slug)->firstOrFail();
        $branchInfo = $restaurantOrder->restaurant_branch_info;
        $restaurantOrderItems = $restaurantOrder->restaurantOrderItems;
        $restaurantOrderContact = $restaurantOrder->restaurantOrderContact;
        $date = Carbon::parse($restaurantOrder->order_date)->format('d M Y');
        return view('restaurant-invoice', compact('restaurantOrder', 'branchInfo', 'restaurantOrderItems', 'restaurantOrderContact', 'date'));
    }

    public function getInvoice($fileName)
    {
        if (Storage::exists(self::PATH . $fileName)) {
            return Storage::download(self::PATH . $fileName);
        }

        $slug = explode('-', $fileName)[0];
        return $this->generateInvoice($slug);
    }
}
