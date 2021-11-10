<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Events\DriverStatusChanged;
use App\Exceptions\BadRequestException;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\RestaurantOrderStatus;
use App\Models\ShopOrder;
use App\Models\ShopOrderDriver;
use App\Models\ShopOrderDriverStatus;
use App\Models\User;
use App\Repositories\Abstracts\RestaurantOrderDriverStatusRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderDriverController extends Controller
{
    use ResponseHelper;

    protected $driver;

    private $repository;

    public function __construct(RestaurantOrderDriverStatusRepositoryInterface $repository)
    {
        $this->driver = Auth::guard('users')->user();
        $this->repository = $repository;
    }

    public function jobList()
    {
        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact')
            ->where('order_status', '!=', 'cancelled')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(3)->startOfDay())
            ->whereHas('drivers', function ($q) {
                $q->where('user_id', $this->driver->id);
            })
            ->whereHas('drivers.status', function ($q) {
                $q->where('status', '!=', 'rejected');
                $q->where('status', '!=', 'no_response');
                $q->where('status', '!=', 'pending');
                $q->where('user_id', $this->driver->id);
            })
            ->orderByDesc('id')
            ->get();

        $shopOrder = ShopOrder::with('drivers', 'drivers.status', 'contact')
            ->where('order_status', '!=', 'cancelled')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(3)->startOfDay())
            ->whereHas('drivers', function ($q) {
                $q->where('user_id', $this->driver->id);
            })
            ->whereHas('drivers.status', function ($q) {
                $q->where('status', '!=', 'rejected');
                $q->where('status', '!=', 'no_response');
                $q->where('status', '!=', 'pending');
                $q->where('user_id', $this->driver->id);
            })
            ->orderByDesc('id')
            ->get();

        $restaurantOrder = $restaurantOrder->merge($shopOrder);

        //group by date and status
        $restaurantOrder = $restaurantOrder->map(function ($data) {
            $data['key'] = Carbon::parse($data["order_date"])->format('Y-m-d');
            return $data;
        })->groupBy('order_status')->map(function ($data, $key) {
            $data = $data->groupBy('key');
            unset($data['key']);
            return $data;
        });

        //change group status into accepted, onRoute, delivered
        $restaurantOrder = $restaurantOrder->toArray();

        $restaurantOrder['accepted'] = isset($restaurantOrder['pending']) ? $restaurantOrder['pending'] : [];

        $restaurantOrder['accepted'] = array_merge($restaurantOrder['accepted'], isset($restaurantOrder['preparing']) ? $restaurantOrder['preparing'] : []);

        $restaurantOrder['accepted'] = array_merge($restaurantOrder['accepted'], isset($restaurantOrder['pickUp']) ? $restaurantOrder['pickUp'] : []);
        unset($restaurantOrder['pending']);
        unset($restaurantOrder['preparing']);
        unset($restaurantOrder['pickUp']);

        //order items with time
        $restaurantOrder = collect($restaurantOrder)->map(function ($data, $key) {

            return collect($data)->map(function ($item) {
                return collect($item)->sortBy('order_date')->values()->all();
            });
        });

        return response()->json($restaurantOrder, 200);
    }

    public function jobDetail($slug)
    {
        $order = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact', 'RestaurantOrderItems')->where('slug', $slug)->first();
        if (!isset($order)) {
            $order = ShopOrder::with('drivers', 'drivers.status', 'contact', 'vendors', 'vendors.shop', 'vendors.shopOrderStatuses')->where('slug', $slug)->first();
        }
        return $this->generateResponse($order, 200);
        // $restaurantOrder->load('drivers', 'drivers.status', 'restaurantOrderContact', 'RestaurantOrderItems')

    }

    public function changeStatus(Request $request, $slug)
    {
        $request->validate(['status' => 'in:accepted,rejected,pickUp,delivered']);

        $driver = Auth::guard('users')->user();

        $type = 'restaurant';

        $order = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact', 'RestaurantOrderItems')->where('slug', $slug)->first();
        if (!isset($order)) {
            $type = 'shop';
            $order = ShopOrder::with('drivers', 'drivers.status', 'contact')->where('slug', $slug)->first();
        }
        if ($type == "restaurant") {
            $orderDriver = RestaurantOrderDriver::where('restaurant_order_id', $order->id)->where('user_id', $driver->id)->firstOrFail();
            $currentDriverStatus = RestaurantOrderDriverStatus::where('restaurant_order_driver_id', $orderDriver->id)->latest()->value('status');
        } else {
            $orderDriver = ShopOrderDriver::where('shop_order_id', $order->id)->where('user_id', $driver->id)->firstOrFail();
            $currentDriverStatus = ShopOrderDriverStatus::where('shop_order_driver_id', $orderDriver->id)->latest()->value('status');
        }

        try {
            $this->repository->validateStatus($currentDriverStatus, $request->status, $order->order_status);
        } catch (BadRequestException $e) {
            return response()->json(['status' => 'failed', 'message' => $e->getMessage()], 406);
        }
        Log::info('here');
        $this->repository->changeStatus($order, $orderDriver, $request->status, $type);

        return response()->json(['status' => 'success'], 201);
    }

    private function changeOrderStatus($driverStatus, $restaurantOrder)
    {
        if ($driverStatus === 'pickUp') {
            $this->createOrderStatus($restaurantOrder, 'onRoute');
        } elseif ($driverStatus === 'delivered') {
            $this->createOrderStatus($restaurantOrder, 'delivered');
            $restaurantOrder->update(['payment_status' => 'success']);
        }
    }

    private function createOrderStatus($restaurantOrder, $status)
    {
        RestaurantOrderStatus::create([
            'status' => $status,
            'restaurant_order_id' => $restaurantOrder->id,
        ]);

        $restaurantOrder->update(['order_status' => $status]);
    }
    public function manualAssignOrder($slug, $driverSlug)
    {
        $driverID = User::where('slug', $driverSlug)->first()->id;

        $orderID = null;

        $order = RestaurantOrder::where('slug', $slug)->first();

        if ($order) {
            $orderID = $order->id;
            $orderDriverStatus = $this->assginToRes($orderID, $driverID);
        } else {
            $orderID = ShopOrder::where('slug', $slug)->first()->id;
            $orderDriverStatus = $this->assginToShop($orderID, $driverID);
        }

        return $this->generateResponse($orderDriverStatus->refresh(), 201);
    }
    private function assginToRes($orderID, $driverID)
    {
        $resOrderDriver = RestaurantOrderDriver::where('restaurant_order_id', $orderID)->where('user_id', $driverID)->first();

        if (empty($resOrderDriver)) {
            $resOrderDriver = RestaurantOrderDriver::create([
                'restaurant_order_id' => $orderID,
                'user_id' => $driverID,
            ]);
        }

        return RestaurantOrderDriverStatus::create([
            'restaurant_order_driver_id' => $resOrderDriver->id,
            'status' => 'accepted',
        ]);
    }
    private function assginToShop($orderID, $driverID)
    {
        $shopOrderDriver = ShopOrderDriver::where('shop_order_id', $orderID)->where('user_id', $driverID)->first();

        if (empty($shopOrderDriver)) {
            $shopOrderDriver = ShopOrderDriver::create([
                'shop_order_id' => $orderID,
                'user_id' => $driverID,
            ]);
        }

        return ShopOrderDriverStatus::create([
            'shop_order_driver_id' => $shopOrderDriver->id,
            'status' => 'accepted',
        ]);
    }
}
