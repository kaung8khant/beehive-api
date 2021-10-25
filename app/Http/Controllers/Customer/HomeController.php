<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\ForbiddenException;
use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Product;
use App\Models\RestaurantBranch;
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
        $branches = RestaurantOrderHelper::getBranches($request)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $this->optimizeBranches($branches);
        return $branches;
    }

    private function getRandomProducts()
    {
        $products = Product::exclude(['description', 'variants', 'created_by', 'updated_by'])
            ->with(['shop' => function ($query) {
                $query->select('id', 'slug', 'name');
            }])
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return $this->optimizeProducts($products);
    }

    private function getNewRestaurants($request)
    {
        $branches = RestaurantOrderHelper::getBranches($request)
            ->orderBy('search_index', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $this->optimizeBranches($branches);
        return $branches;
    }

    private function getNewProducts()
    {
        $products = Product::exclude(['description', 'variants', 'created_by', 'updated_by'])
            ->with(['shop' => function ($query) {
                $query->select('id', 'slug', 'name');
            }])
            ->whereHas('shop', function ($query) {
                $query->where('is_enable', 1);
            })
            ->where('is_enable', 1)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $this->optimizeProducts($products);
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

        $restaurantBranches = RestaurantBranch::search($request->keyword)->where('is_enable', 1)->where('is_restaurant_enable', 1)->get($request->size);
        $menus = Menu::search($request->keyword)->where('is_enable', 1)->where('is_restaurant_enable', 1)->get();

        $restaurantIdsFromBranches = $restaurantBranches->pluck('restaurant_id');
        $restaurantIdsFromMenus = $menus->pluck('restaurant_id');
        $restaurantIds = $restaurantIdsFromBranches->merge($restaurantIdsFromMenus)->unique()->values()->toArray();

        $restaurantBranches = RestaurantOrderHelper::getBranches($request)
            ->whereIn('restaurant_id', $restaurantIds)
            ->orderBy('search_index', 'desc')
            ->orderBy('distance', 'asc')
            ->paginate($request->size)
            ->items();

        foreach ($restaurantBranches as $branch) {
            $branch->restaurant->makeHidden(['created_by', 'updated_by', 'commission', 'first_order_date']);
            $branch->restaurant->availableTags->makeHidden(['created_by', 'updated_by']);
        }

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

        $products = Product::search($request->keyword)->where('is_enable', 1)->where('is_shop_enable', 1)->paginate($request->size);
        $products->makeHidden(['description', 'variants', 'created_by', 'updated_by', 'covers']);
        $products = $products->items();

        if ($homeSearch) {
            return $this->generateProductResponse($products, 200, 'home');
        }

        return $this->generateProductResponse($products, 200);
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

    private function optimizeBranches($branches)
    {
        foreach ($branches as $branch) {
            $branch->makeHidden(['address', 'contact_number']);
            $branch->restaurant->makeHidden(['created_by', 'updated_by', 'commission']);
            $branch->restaurant->setAppends(['rating', 'images', 'covers']);
        }
    }

    private function optimizeProducts($products)
    {
        return $products->map(function ($product) {
            $product->makeHidden(['covers']);
            $product->shop->makeHidden(['rating', 'images', 'covers', 'first_order_date']);
            return $product->images->count() > 0 ? $product : null;
        })->filter()->values();
    }
}
