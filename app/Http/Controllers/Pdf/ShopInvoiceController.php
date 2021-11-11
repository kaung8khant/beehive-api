<?php

namespace App\Http\Controllers\Pdf;

use App\Events\ShopOrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\Storage;

class ShopInvoiceController extends Controller
{
    const PATH = 'pdf/shops/';

    public function generateInvoice($slug)
    {
        $shopOrder = ShopOrder::where('slug', $slug)->firstOrFail();
        event(new ShopOrderUpdated($shopOrder));

        $fileName = $shopOrder->slug . '-' . $shopOrder->invoice_id . '.pdf';
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
