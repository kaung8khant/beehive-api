<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\PromocodeHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use Illuminate\Http\Request;

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
}
