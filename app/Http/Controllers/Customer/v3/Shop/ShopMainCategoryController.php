<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopMainCategory;
use Illuminate\Http\Request;

class ShopMainCategoryController extends Controller
{
    public function index(Request $request)
    {
        $shopMainCategories = ShopMainCategory::exclude(['created_by', 'updated_by'])->orderBy('name', 'asc');

        if ($request->populate && (bool) $request->populate) {
            $shopMainCategories->with(['shopCategories' => function ($query) {
                $query->exclude(['created_by', 'updated_by'])->orderBy('name', 'asc');
            }]);
        }

        return $shopMainCategories->get();
    }
}
