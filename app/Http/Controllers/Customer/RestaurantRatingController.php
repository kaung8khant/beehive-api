<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ResponseHelper;
use App\Models\RestaurantOrder;
use App\Models\RestaurantRating;

class RestaurantRatingController extends Controller
{
    use ResponseHelper;

    public function store(Request $request)
    {
        $validator = $this->validateRating($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $validatedData = $validator->validated();
        $customerId = Auth::guard('customers')->user()->id;
        $restaurantOrder = $this->getRestaurantOrder($validatedData['order_slug'], $customerId);

        for ($i = 0; $i < count($validatedData['ratings']); $i++) {
            if ($validatedData['ratings'][$i]['target_type'] === 'restaurant') {
                $validatedData['ratings'][$i]['target_id'] = $restaurantOrder->restaurant->id;
            }

            $validatedData['ratings'][$i]['restaurant_order_id'] = $restaurantOrder->id;
            $validatedData['ratings'][$i]['source_type'] = 'customer';
            $validatedData['ratings'][$i]['source_id'] = $customerId;

            $rating = $this->getRating($validatedData['ratings'][$i]);

            if ($rating) {
                $rating->update($validatedData['ratings'][$i]);
            } else {
                RestaurantRating::create($validatedData['ratings'][$i]);
            }
        }

        return $this->generateResponse('Success.', 200, TRUE);
    }

    private function validateRating($request)
    {
        return Validator::make($request->all(), [
            'order_slug' => 'required|exists:App\Models\RestaurantOrder,slug',
            'ratings' => 'required|array',
            'ratings.*.target_type' => 'required|in:restaurant,biker',
            'ratings.*.rating' => 'required|integer',
            'ratings.*.review' => 'nullable|string',
        ]);
    }

    private function getRestaurantOrder($slug, $customerId)
    {
        return RestaurantOrder::where('customer_id', $customerId)->where('slug', $slug)->firstOrFail();
    }

    private function getRating($data)
    {
        return RestaurantRating::where('target_type', $data['target_type'])
            ->where('source_type', $data['source_type'])
            ->where('restaurant_order_id', $data['restaurant_order_id'])
            ->first();
    }
}
