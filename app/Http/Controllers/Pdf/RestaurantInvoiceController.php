<?php

namespace App\Http\Controllers\Pdf;

use App\Events\RestaurantOrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use Illuminate\Support\Facades\Storage;

class RestaurantInvoiceController extends Controller
{
    const PATH = 'pdf/restaurants/';

    public function generateInvoice($slug)
    {
        $restaurantOrder = RestaurantOrder::where('slug', $slug)->firstOrFail();
        event(new RestaurantOrderUpdated($restaurantOrder));

        $fileName = $restaurantOrder->slug . '-' . $restaurantOrder->invoice_id . '.pdf';
        return Storage::download(self::PATH . $fileName);
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
