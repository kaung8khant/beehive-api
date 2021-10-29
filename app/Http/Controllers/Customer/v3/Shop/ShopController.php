<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use ResponseHelper;

    public function getByBrand(Request $request, Brand $brand)
    {
        $shopIds = Product::where('brand_id', $brand->id)
            ->where('is_enable', 1)
            ->pluck('shop_id')
            ->unique()
            ->values()
            ->toArray();

        $shops = Shop::exclude(['address', 'city', 'township', 'notify_numbers'])
            ->whereIn('id', $shopIds)
            ->where('is_enable', 1)
            ->orderBy('name', 'asc')
            ->paginate($request->size);

        $shops->makeHidden(['created_by', 'updated_by', 'first_order_date']);
        return $this->generateResponse($shops->items(), 200, false, $shops->lastPage());
    }
}
