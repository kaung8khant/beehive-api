<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use App\Models\RestaurantBranch;
use App\Models\Product;
use App\Services\FirebaseService;

class HomeController extends Controller
{
    use ResponseHelper;

    public function getSuggestions(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $result = [
            'restaurant_branches' => $this->getRestaurantBranches($request)->inRandomOrder()->limit(10)->get(),
            'products' => $this->getRandomProducts(),
        ];

        return $this->generateResponse($result, 200);
    }

    public function getNewArrivals(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $result = [
            'restaurant_branches' => $this->getRestaurantBranches($request)->latest()->limit(10)->get(),
            'products' => $this->getNewProducts(),
        ];

        return $this->generateResponse($result, 200);
    }

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function getRestaurantBranches($request, $suggestion = FALSE)
    {
        $radius = config('system.restaurant_search_radius');

        return RestaurantBranch::with('restaurant')
            ->selectRaw('id, slug, name, address, contact_number, opening_time, closing_time, is_enable, restaurant_id, township_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->where('is_enable', 1)
            ->having('distance', '<', $radius);
    }

    private function getRandomProducts()
    {
        return Product::with('shop')
            ->where('is_enable', 1)
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }

    private function getNewProducts()
    {
        return Product::with('shop')
            ->where('is_enable', 1)
            ->latest()
            ->limit(10)
            ->get();
    }

    public function search(Request $request)
    {
        $validator = $this->validateSearch($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $result = [
            'restaurant_branches' => $this->searchRestaurantBranches($request, TRUE),
            'products' => $this->searchProduct($request, TRUE),
        ];

        return $this->generateResponse($result, 200);
    }

    public function searchRestaurantBranches(Request $request, $homeSearch = FALSE)
    {
        $validator = $this->validateSearch($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }

        $restaurantBranches = $this->getRestaurantBranches($request)
            ->where('name', 'LIKE', '%' . $request->keyword . '%')
            ->orWhereHas('restaurant', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhereHas('availableCategories', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('availableTags', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    });
            })
            ->orWhereHas('availableMenus', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhere('description_mm', 'LIKE', '%' . $request->keyword . '%');
            })
            ->orderBy('distance', 'asc')
            ->paginate($request->size)
            ->items();

        if ($homeSearch) {
            return $restaurantBranches;
        }

        return $this->generateResponse($restaurantBranches, 200);
    }
    public function searchProduct(Request $request, $homeSearch = FALSE)
    {

        $validator  = Validator::make($request->all(), [
            'keyword' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, TRUE);
        }
        $product = Product::with('shop')
            ->where('name', 'LIKE', '%' . $request->keyword . '%')
            ->orWhere('description', 'LIKE', '%' . $request->keyword . '%')
            ->orWhere('description_mm', 'LIKE', '%' . $request->keyword . '%')
            ->whereHas('shop', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%');
            })
            ->orWhereHas('shopCategory', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%');
            })
            ->orWhereHas('brand', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%');
            })
            ->orWhereHas('shopSubCategory', function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%');
            })
            ->with('productVariations')
            ->with('productVariations.productVariationValues')
            ->get();

        if ($homeSearch) {
            return $product;
        }

        return $this->generateResponse($product, 200);
    }

    private function validateSearch($request)
    {
        return Validator::make($request->all(), [
            'keyword' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }
    public function noti(Request $request, FirebaseService $firebase)
    {
        $firebase->sendNotification($request);
    }
}
