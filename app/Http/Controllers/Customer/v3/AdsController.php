<?php

namespace App\Http\Controllers\Customer\v3;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Models\Brand;
use App\Models\Shop;

class AdsController extends Controller
{
    use ResponseHelper;

    public function GetByBrand(Brand $brand)
    {
        $ads = $this->getAds('brand', $brand->slug);
        return $this->generateResponse($ads, 200);
    }

    public function getByShop(Shop $shop)
    {
        $ads = $this->getAds('shop', $shop->slug);
        return $this->generateResponse($ads, 200);
    }

    private function getAds($targetType, $value)
    {
        return Ads::exclude(['created_by', 'updated_by'])
            ->where('type', 'banner')
            ->where('source', 'shop')
            ->where('target_type', $targetType)
            ->where('value', $value)
            ->orderBy('search_index', 'desc')
            ->get();
    }
}
