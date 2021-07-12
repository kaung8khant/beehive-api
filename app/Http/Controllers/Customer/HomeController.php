<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Helpers\RestaurantOrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public function test()
    {
        $restaurant_orders = DB::select('Select od.id,od.restaurant_order_id,od.user_id,ods.status from (SELECT sod1.* FROM restaurant_order_drivers sod1
        JOIN (SELECT restaurant_order_id, MAX(created_at) created_at FROM restaurant_order_drivers GROUP BY restaurant_order_id) sod2
            ON sod1.restaurant_order_id = sod2.restaurant_order_id AND sod1.created_at = sod2.created_at) od left join restaurant_order_driver_statuses ods on od.id=ods.restaurant_order_driver_id where ods.status="pending"');
        $orderDriverStatus = [];
        foreach ($restaurant_orders as $order) {
            $reOrder = RestaurantOrderDriverStatus::where('restaurant_order_driver_id', $order->id)->get();
            array_push($orderDriverStatus, $reOrder);
            $resOrderDriver = RestaurantOrderDriver::with('driver')->where('restaurant_order_id', $order->restaurant_order_id)->get()->pluck('driver.slug');
            $order->{'drivers'} = $resOrderDriver;
        }

        return response()->json(['all_pending' => $restaurant_orders, "res_order_driver_status" => $orderDriverStatus, "drivers" => $resOrderDriver]);
    }

    public function getSuggestions(Request $request)
    {
        $validator = $this->validateLocation($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $restaurantBranches = RestaurantOrderHelper::getBranches($request)->inRandomOrder()->limit(10)->get();

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

        $restaurantBranches = RestaurantOrderHelper::getBranches($request)->orderBy('id', 'desc')->limit(10)->get();

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
        $ads = Ads::where('type', $request->type);

        if ($request->source) {
            $ads = $ads->where('source', $request->source);
        }

        $result = $ads->paginate(10)->map(function ($data) {
            return [
                'images' => $data->images,
                'source' => $data->source,
                'type' => $data->type,
            ];
        });

        return $this->generateResponse($result, 200);
    }
}
