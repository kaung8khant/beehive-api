<?php

namespace App\Http\Controllers\Customer\v3\Shop;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;

class BrandController extends Controller
{
    use ResponseHelper;

    public function getAllBrands()
    {
        $brands = Brand::exclude(['created_by', 'updated_by'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return $this->generateResponse($brands->items(), 200, null, $brands->lastPage());
    }
}
