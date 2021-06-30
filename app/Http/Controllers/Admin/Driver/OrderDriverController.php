<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderDriver;
use App\Models\RestaurantOrderDriverStatus;
use App\Models\RestaurantOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderDriverController extends Controller
{
    use ResponseHelper;

    protected $driver;

    public function __construct()
    {
        $this->driver = Auth::guard('users')->user();
    }

    public function jobList()
    {
        $restaurantOrder = RestaurantOrder::with('drivers', 'drivers.status', 'restaurantOrderContact')
            ->where('order_status', '!=', 'cancelled')
            ->whereHas('drivers', function ($q) {
                $q->where('user_id', $this->driver->id);
            })
            ->whereHas('drivers.status', function ($q) {
                $q->where('status', '!=', 'rejected');
                $q->where('status', '!=', 'no_response');
                $q->where('status', '!=', 'pending');
            })
            ->orderByDesc('id')
            ->get();

        return response()->json($restaurantOrder, 200);
    }

    public function jobDetail(RestaurantOrder $restaurantOrder)
    {
        return $this->generateResponse($restaurantOrder->load('drivers', 'drivers.status', 'restaurantOrderContact'), 200);
    }

    public function changeStatus(Request $request, RestaurantOrder $restaurantOrder)
    {
        $request->validate(['status' => 'in:accepted,rejected,pickup,delivered']);

        if (in_array($restaurantOrder->order_status, ['delivered', 'cancelled'])) {
            $message = 'The order is already ' . $restaurantOrder->order_status;
            return response()->json(['status' => 'failed', 'message' => $message], 406);
        }

        $restaurantOrderDriver = RestaurantOrderDriver::where('restaurant_order_id', $restaurantOrder->id)->where('user_id', $this->driver->id)->firstOrFail();
        $driverStatus = RestaurantOrderDriverStatus::where('restaurant_order_driver_id', $restaurantOrderDriver->id)->latest()->value('status');

        if ($request->status === $driverStatus) {
            return response()->json(['status' => 'failed', 'message' => 'Already ' . $driverStatus], 406);
        }

        if (in_array($driverStatus, ['no-response', 'rejected'])) {
            $message = 'Already ' . $driverStatus;
            return response()->json(['status' => 'failed', 'message' => $message], 406);
        }

        $checkStatus = $this->checkStatus($request->status, $driverStatus, $restaurantOrder->order_status);
        if ($checkStatus !== true && $checkStatus['status'] === 'failed') {
            return response()->json(['status' => $checkStatus['status'], 'message' => $checkStatus['message']], $checkStatus['code']);
        }

        RestaurantOrderDriverStatus::create([
            'restaurant_order_driver_id' => $restaurantOrderDriver->id,
            'status' => $request->status,
        ]);

        $this->changeOrderStatus($request->status, $restaurantOrder);

        return response()->json(['status' => 'success'], 201);
    }

    private function checkStatus($status, $driverStatus, $orderStatus)
    {
        switch ($status) {
            case 'accepted':
                if (in_array($driverStatus, ['rejected', 'no-response', 'pickup', 'delivered'])) {
                    return ['status' => 'failed', 'message' => 'Already ' . $driverStatus, 'code' => 406];
                }
                return true;
                break;

            case 'rejected':
                if (in_array($driverStatus, ['no-response', 'pickup', 'delivered'])) {
                    return ['status' => 'failed', 'message' => 'Already ' . $driverStatus, 'code' => 406];
                }
                return true;
                break;

            case 'pickup':
                if ($driverStatus !== 'accepted') {
                    return ['status' => 'failed', 'message' => 'Please accept first.', 'code' => 406];
                }

                if ($orderStatus !== 'pickUp') {
                    return ['status' => 'failed', 'message' => 'The order is not ready to pick up yet.', 'code' => 406];
                }
                return true;
                break;

            case 'delivered':
                if ($driverStatus !== 'pickup') {
                    return ['status' => 'failed', 'message' => 'Please pick up first.', 'code' => 406];
                }

                if ($orderStatus !== 'onRoute') {
                    return ['status' => 'failed', 'message' => 'The order is not on route yet.', 'code' => 406];
                }
                return true;
                break;

            default:
                return true;
        }
    }

    private function changeOrderStatus($driverStatus, $restaurantOrder)
    {
        if ($driverStatus === 'pickup') {
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
}
