<?php

namespace App\Http\Controllers\Cart;

use App\Exceptions\BadRequestException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopCartController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validator = $this->validateProductCart($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        try {
            return $product;
        } catch (BadRequestException $e) {
            return $this->generateResponse($e->getMessage(), 400, true);
        }
    }

    private function validateProductCart($request)
    {
        return Validator::make($request->all(), [
            'customer_slug' => 'required|exists:App\Models\Customer,slug',
            'restaurant_branch_slug' => 'required|exists:App\Models\RestaurantBranch,slug',
            'variant_slug' => 'required|exists:App\Models\MenuVariant,slug',
            'toppings' => 'nullable|array',
            'toppings.*.slug' => 'required|exists:App\Models\MenuTopping',
            'toppings.*.quantity' => 'required|integer',
        ]);
    }
}
