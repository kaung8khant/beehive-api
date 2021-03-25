<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromocodeController extends Controller
{
    use ResponseHelper, PromocodeHelper;

    protected $customer_id;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer_id = Auth::guard('customers')->user()->id;
        }
    }

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

    public function validatePromoCode($slug)
    {
        $promo = $this->validatePromo($slug);
        return $this->generateResponse($promo, 200);
    }

}
