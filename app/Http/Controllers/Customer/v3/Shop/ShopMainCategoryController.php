<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ShopMainCategory;
use Illuminate\Http\Request;

class ShopMainCategoryController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        $shopMainCategories = ShopMainCategory::exclude(['created_by', 'updated_by'])->orderBy('search_index', 'desc')->orderBy('name', 'asc');

        if ($request->populate && (bool) $request->populate) {
            $shopMainCategories
                ->with([
                    'shopCategories' => function ($query) {
                        $query->exclude(['created_by', 'updated_by'])
                            ->whereHas('products', fn($query) => $query->where('is_enable', 1))
                            ->orderBy('search_index', 'desc')
                            ->orderBy('name', 'asc');
                    },
                    'shopCategories.shopSubCategories' => function ($query) {
                        $query->exclude(['created_by', 'updated_by'])
                            ->whereHas('products', fn($query) => $query->where('is_enable', 1))
                            ->orderBy('search_index', 'desc')
                            ->orderBy('name', 'asc');
                    },
                ]);
        }

        return ResponseHelper::generateResponse($shopMainCategories->get(), 200);
    }
}
