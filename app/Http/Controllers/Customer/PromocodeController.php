<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\BadRequestException;
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
        $promo_list = Promocode::with('rules');
        if ($request->type) {
            $promo_list = $promo_list->where('usage', $request->type);
        }

        $promo_list = $promo_list->get();
        $result = [];
        foreach ($promo_list as $promo) {
            if ($this->validateRule($promo->rules, $promo->id)) {
                array_push($result, $promo);
            }
        }

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
        if (isset($validatedData['promo_code_slug'])) {
            // may require amount validation.
            $promocode = Promocode::where('slug', $validatedData['promo_code_slug'])->with('rules')->firstOrFail();

            PromocodeHelper::validatePromocodeUsage($promocode, $usage);
            PromocodeHelper::validatePromocodeRules($promocode, $validatedData['order_items'], $validatedData['subTotal'], $customer, $usage);
            $promocodeAmount = PromocodeHelper::calculatePromocodeAmount($promocode, $validatedData['order_items'], $validatedData['subTotal'],$usage);

            $response['promocode_id'] = $promocode->id;
            $response['promocode'] = $promocode->code;
            $response['promocode_amount'] = $promocodeAmount;
            return $this->generateResponse($response, 200);
        } else {
            throw new BadRequestException("Promocode not found.", 400);
        }

    }
}
