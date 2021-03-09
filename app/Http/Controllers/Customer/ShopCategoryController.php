<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShopCategory;

class ShopCategoryController extends Controller
{
    public function index(Request $request)
    {
        return ShopCategory::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function getShopByCategory($slug)
    {
        $shopCategory = ShopCategory::where('slug', $slug)->firstOrFail();
        return $shopCategory->shop()->paginate(10);
    }
}
