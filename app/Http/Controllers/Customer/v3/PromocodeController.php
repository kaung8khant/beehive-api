<?php

namespace App\Http\Controllers\Customer\v3;

use App\Exceptions\ForbiddenException;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Helpers\v3\PromocodeHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromocodeController extends Controller
{
    use ResponseHelper, PromocodeHelper, StringHelper;

    protected $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function validatePromoCode(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        if (isset($request['restaurant_branch_slug'])) {
            $validatedData = \App\Helpers\RestaurantOrderHelper::validateOrderV3($request);
        } else {
            $validatedData = \App\Helpers\ShopOrderHelper::validateOrder($request);
        }

        if (gettype($validatedData) == 'string') {
            return $this->generateResponse($validatedData, 422, true);
        }

        if ($validatedData['promo_code']) {
            $type = isset($request['restaurant_branch_slug']) ? 'restaurant' : 'shop';

            $validatedData = $this->getPromoData($validatedData, $type);
        }

        return $this->generateResponse($validatedData, 200);
    }

    private function getPromoData($validatedData, $type)
    {
        $promocode = Promocode::where('code', strtoupper($validatedData['promo_code']))->with('rules')->latest('created_at')->first();
        if (!$promocode) {
            throw new ForbiddenException('Promocode not found');
        }

        $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, $type);
        if (!$validUsage) {
            throw new ForbiddenException('Invalid promocode usage for' . $type . '.');
        }

        $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $this->customer, $type);
        if (!$validRule) {
            throw new ForbiddenException('Invalid promocode.');
        }

        $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], $type);

        $validatedData['promocode_id'] = $promocode->id;
        $validatedData['promocode'] = $promocode->code;
        $validatedData['promocode_amount'] = $promocodeAmount;

        return $validatedData;
    }
}
