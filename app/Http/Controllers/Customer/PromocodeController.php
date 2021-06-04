<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromocodeController extends Controller
{
    use ResponseHelper, PromocodeHelper, StringHelper;

    public function index(Request $request)
    {
        $size = $request->size ? $request->size : 100;
        $page = $request->page ? $request->page : 1;

        $promoLists = Promocode::with('rules');

        if ($request->type) {
            $promoLists = $promoLists->where('usage', $request->type);
        }

        $result = $promoLists->orderBy('id', 'desc')->get()->filter(function ($promo) {
            return $this->validateRule($promo->rules, $promo->id);
        })->slice(($page - 1) * $size, $size)->values();

        return $this->generateResponse($result, 200);
    }

    public function validatePromoCode(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $request['customer_slug'] = Auth::guard('customers')->user()->slug;

        // validate order
        $validatedData = [];

        if (isset($request['restaurant_branch_slug'])) {
            $validatedData = \App\Helpers\RestaurantOrderHelper::validateOrder($request, true);

        } else {
            $validatedData = \App\Helpers\ShopOrderHelper::validateOrder($request, true);
        }
        if (gettype($validatedData) == "string") {
            return $this->generateShopOrderResponse($validatedData, 422, true);
        }
        // get Customer Info
        $customer = Auth::guard('customers')->user();
        // append customer data
        $validatedData['customer_id'] = Auth::guard('customers')->user()->id;

        if (isset($request['restaurant_branch_slug'])) {
            $validatedData = \App\Helpers\RestaurantOrderHelper::prepareRestaurantVariations($validatedData);
        } else {
            $validatedData = \App\Helpers\ShopOrderHelper::prepareProductVariations($validatedData);
        }

        $usage = isset($request['restaurant_branch_slug']) ? 'restaurant' : 'shop';

        // validate promocode
        if (isset($validatedData['promo_code'])) {
            // may require amount validation.
            $promocode = Promocode::where('code', $validatedData['promo_code'])->with('rules')->latest('created_at')->first();
            if (!isset($promocode) && empty($promocode)) {
                return $this->generateResponse("Promocode not found.", 400, true);
            }
            $validUsage = PromocodeHelper::validatePromocodeUsage($promocode, $usage);
            if (!$validUsage) {
                return $this->generateShopOrderResponse("Invalid promocode usage for shop.", 422, true);
            }
            $validRule = PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, $usage);
            if (!$validRule) {
                return $this->generateShopOrderResponse("Invalid promocode rule.", 422, true);
            }
            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'], $usage);

            $response['promocode_id'] = $promocode->id;
            $response['promocode'] = $promocode->code;
            $response['promocode_amount'] = $promocodeAmount;
            return $this->generateResponse($response, 200);
        } else {
            return $this->generateResponse("Promocode not found.", 400, true);
        }
    }
}
