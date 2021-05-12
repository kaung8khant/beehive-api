<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RestaurantBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    use ResponseHelper;

    private $customer;

    public function __construct()
    {
        if (Auth::guard('customers')->check()) {
            $this->customer = Auth::guard('customers')->user();
        }
    }

    public function getSuggestions(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantBranches = $this->getRestaurantBranches($request)->inRandomOrder()->limit(10)->get();

        $result = [
            'restaurant_branches' => $this->generateBranchResponse($restaurantBranches, 200, 'home'),
            'products' => $this->generateProductResponse($this->getRandomProducts(), 200, 'home'),
        ];

        return $this->generateResponse($result, 200);
    }

    public function getNewArrivals(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantBranches = $this->getRestaurantBranches($request)->latest()->limit(10)->get();

        $result = [
            'restaurant_branches' => $this->generateBranchResponse($restaurantBranches, 200, 'home'),
            'products' => $this->generateProductResponse($this->getNewProducts(), 200, 'home'),
        ];

        return $this->generateResponse($result, 200);
    }

    public function getFavorite(Request $request)
    {
        $product = [];
        $restaurant = [];

        if ($this->customer) {

            $product = $this->customer->favoriteProducts()->with('shopCategory', 'shopSubCategory', 'brand')->paginate($request->size)->items();

            $restaurant = $this->customer->favoriteRestaurants()
                ->with(['restaurantBranches' => function ($query) use ($request) {
                    $this->getBranchQuery($query, $request)->orderBy('distance', 'asc');
                }])
                ->paginate($request->size)
                ->pluck('restaurantBranches')
                ->collapse();
        }
        $result = [
            'restaurant_branches' => $this->generateBranchResponse($restaurant, 200, 'home'),
            'products' => $this->generateProductResponse($product, 200, 'home'),
        ];

        return $this->generateResponse($result, 200);
    }

    // duplicate from restaurant controller
    private function getBranchQuery($query, $request)
    {
        $radius = config('system.restaurant_search_radius');

        return $query->with('restaurant')
            ->with('restaurant.availableTags')
            ->selectRaw('id, slug, name, address, contact_number, opening_time, closing_time, is_enable, restaurant_id, township_id,
            ( 6371 * acos( cos(radians(?)) *
                cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)) )
            ) AS distance', [$request->lat, $request->lng, $request->lat])
            ->where('is_enable', 1)
            ->having('distance', '<', $radius);
    }

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function getRestaurantBranches($request, $suggestion = false)
    {
        $radius = config('system.restaurant_search_radius');

        return RestaurantBranch::with('restaurant')
            ->with('restaurant.availableTags')
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
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $result = [
            'restaurant_branches' => $this->searchRestaurantBranches($request, true),
            'products' => $this->searchProduct($request, true),
        ];

        return $this->generateResponse($result, 200);
    }

    public function searchRestaurantBranches(Request $request, $homeSearch = false)
    {
        $validator = $this->validateSearch($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantBranches = $this->getRestaurantBranches($request)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhereHas('restaurant', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('availableMenus', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%')
                            ->orWhere('description', 'LIKE', '%' . $request->keyword . '%');
                    });
            })
            ->orderBy('distance', 'asc')
            ->paginate($request->size)
            ->items();

        if ($homeSearch) {
            return $this->generateBranchResponse($restaurantBranches, 200, 'home');
        }

        return $this->generateBranchResponse($restaurantBranches, 200);
    }

    public function searchProduct(Request $request, $homeSearch = false)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $product = Product::with('shop')
            ->where('is_enable', 1)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->keyword . '%')
                    ->whereHas('shop', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('shopCategory', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('brand', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('shopSubCategory', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    });
            })
            ->with('productVariations')
            ->with('productVariations.productVariationValues')
            ->get();

        if ($homeSearch) {

            return $this->generateProductResponse($product, 200, 'home');
        }

        return $this->generateProductResponse($product, 200);
    }

    private function validateSearch($request)
    {
        return Validator::make($request->all(), [
            'keyword' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    public function registerCustomerToken(Request $request)
    {
        $customerId = Auth::guard('customers')->user()->id;
        $customer = Customer::where('id', $customerId)->firstOrFail();
        $customer->device_token = $request->token;
        $customer->update();

        return $this->generateResponse("Success.", 200);
    }

    public function getAds(Request $request)
    {
        if ($request->source) {
            return Ads::where('source', $request->source)
                ->where('type',  $request->type)
                ->get();
        }
        return Ads::where('type', $request->type)
            ->get();
    }
}
