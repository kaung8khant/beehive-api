<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ForbiddenException;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
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

        $result = [
            'restaurant_branches' => $this->generateBranchResponse($this->getRandomRestaurants($request), 200, 'home'),
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

        $result = [
            'restaurant_branches' => $this->generateBranchResponse($this->getNewRestaurants($request), 200, 'home'),
            'products' => $this->generateProductResponse($this->getNewProducts(), 200, 'home'),
        ];

        return $this->generateResponse($result, 200);
    }

    public function getFavorite(Request $request)
    {
        $product = [];
        $restaurant = [];

        if ($this->customer) {
            $product = $this->customer->favoriteProducts()
                ->with('shopCategory', 'shopSubCategory', 'brand')
                ->whereHas('shop', function ($query) {
                    $query->where('is_enable', 1);
                })
                ->where('is_enable', 1)
                ->paginate($request->size)
                ->items();

            $restaurant = $this->customer->favoriteRestaurants()
                ->with(['restaurantBranches' => function ($query) use ($request) {
                    RestaurantOrderHelper::getBranchQuery($query, $request)->orderBy('distance', 'asc');
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

    private function validateLocation($request)
    {
        return Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);
    }

    private function getRandomRestaurants($request)
    {
        return RestaurantOrderHelper::getBranches($request)
            ->orderBy('search_index', 'desc')
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }

    private function getRandomProducts()
    {
        return Product::with('shop')
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }

    private function getNewRestaurants($request)
    {
        return RestaurantOrderHelper::getBranches($request)
            ->orderBy('search_index', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
    }

    private function getNewProducts()
    {
        return Product::with('shop')
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
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

        $restaurantBranches = RestaurantOrderHelper::getBranches($request)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->keyword . '%')
                    ->orWhereHas('restaurant', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('availableMenus', function ($q) use ($request) {
                        $q->where('name', 'LIKE', '%' . $request->keyword . '%')
                            ->orWhere('description', 'LIKE', '%' . $request->keyword . '%')
                            ->orWhereHas('restaurantCategory', function ($p) use ($request) {
                                $p->where('name', 'LIKE', '%' . $request->keyword . '%');
                            });
                    });
            })
            ->orderBy('search_index', 'desc')
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
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
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
            ->paginate($request->size)
            ->items();

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
        try {
            $this->checkMobileVersion($request);
        } catch (ForbiddenException $e) {
            return $this->generateResponse($e->getMessage(), 403, true);
        }

        $ads = Ads::where('type', $request->type);

        if ($request->source) {
            $ads = $ads->where('source', $request->source);
        }

        $result = $ads->exclude(['contact_person', 'company_name', 'phone_number', 'email', 'created_by', 'updated_by', 'created_at', 'updated_at'])->paginate(10)->items();

        return $this->generateResponse($result, 200);
    }

    private function checkMobileVersion($request)
    {
        $platform = $request->header('X-APP-TYPE');
        $appVersion = $request->header('X-APP-VERSION');

        if (!$platform || !$appVersion) {
            throw new ForbiddenException('Your application is out of date. Please update your application to get the latest features.');
        }

        if ($platform === 'android') {
            if ($platform === 'android') {
                $currentVersion = Setting::where('key', 'android_version')->value('value');
            } else {
                $currentVersion = Setting::where('key', 'ios_version')->value('value');
            }

            $appVersion = str_replace('.', '', $appVersion);
            $correctVersion = '';

            for ($i = 0; $i < strlen($appVersion); $i++) {
                $correctVersion .= $appVersion[$i];

                if ($i !== strlen($appVersion) - 1) {
                    $correctVersion .= '.';
                }
            }

            if ($correctVersion < $currentVersion) {
                throw new ForbiddenException('Your application is out of date. Please update your application to get the latest features.');
            }
        }
    }
}
