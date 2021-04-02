<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\ShopRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopRatingController extends Controller
{
    use ResponseHelper;

    public function store(Request $request)
    {
        $validator = $this->validateRating($request);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $validatedData = $validator->validated();

        $shopOrder = $this->getShopOrder($validatedData['order_slug']);

        $customerId = Auth::guard('customers')->user()->id;

        if ($customerId !== $shopOrder->customer_id) {
            return $this->generateResponse(['Unauthorize process.'], 401);
        }

        for ($i = 0; $i < count($validatedData['ratings']); $i++) {
            if ($validatedData['ratings'][$i]['target_type'] === 'shop') {
                $shop_id = Shop::where('slug', $validatedData['ratings'][$i]['target_slug'])->first()->id;
                $validatedData['ratings'][$i]['target_id'] = $shop_id;
            } elseif ($validatedData['ratings'][$i]['target_type'] === 'product') {
                $product_id = Product::where('slug', $validatedData['ratings'][$i]['target_slug'])->first()->id;
                $validatedData['ratings'][$i]['target_id'] = $product_id;
            }

            $validatedData['ratings'][$i]['shop_order_id'] = $shopOrder->id;
            $validatedData['ratings'][$i]['source_type'] = 'customer';
            $validatedData['ratings'][$i]['source_id'] = $customerId;

            $rating = $this->getRating($validatedData['ratings'][$i]);

            if ($rating) {
                $rating->update($validatedData['ratings'][$i]);
            } else {
                ShopRating::create($validatedData['ratings'][$i]);
            }
        }

        return $this->generateResponse('Success.', 200, true);
    }

    private function validateRating($request)
    {
        return Validator::make($request->all(), [
            'order_slug' => 'required|exists:App\Models\ShopOrder,slug',
            'ratings' => 'required|array',
            'ratings.*.target_type' => 'required|in:shop,biker,product',
            'ratings.*.rating' => 'required|integer',
            'ratings.*.review' => 'nullable|string',
        ]);
    }

    private function getShopOrder($slug)
    {
        return ShopOrder::where('slug', $slug)->first();
    }

    private function getRating($data)
    {
        return ShopRating::where('target_type', $data['target_type'])
            ->where('source_type', $data['source_type'])
            ->where('shop_order_id', $data['shop_order_id'])
            ->where('source_id', $data['source_id'])
            ->where('target_id', $data['target_id'])
            ->where('target_type', $data['target_type'])
            ->first();
    }
}
