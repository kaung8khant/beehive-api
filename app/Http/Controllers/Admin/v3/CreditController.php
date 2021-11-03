<?php

namespace App\Http\Controllers\Admin\v3;

use App\Events\DataChanged;
use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    private $user;

    public function __construct()
    {
        if (Auth::guard('users')->check()) {
            $this->user = Auth::guard('users')->user();
        }
    }

    public function index(Customer $customer)
    {
        $credit = $customer->credit;
        $credit->remaining_amount = $this->getRemainingCredit($customer);
        return $customer->credit;
    }

    public function updateOrCreate(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'required|numeric',
        ]);

        $method = $customer->credit ? 'update' : 'create';

        $credit = $customer->credit ?: new Credit;
        $credit->amount = $request->amount;
        $customer->credit()->save($credit);

        DataChanged::dispatch($this->user, $method, 'credits', $customer->slug, $request->url(), 'success', $request->all());

        return $customer->refresh()->credit;
    }

    public function delete(Request $request, Customer $customer)
    {
        DataChanged::dispatch($this->user, 'delete', 'credits', $customer->slug, $request->url(), 'success');
        $customer->credit()->delete();

        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function getRemainingCredit($customer)
    {
        $restaurantOrders = DB::table('restaurant_orders')
            ->select('id', 'created_at', DB::raw("'restaurant' as source"))
            ->where('customer_id', $customer->id)
            ->where('payment_mode', 'Credit')
            ->where('payment_status', 'success')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);

        $orders = DB::table('shop_orders')
            ->select('id', 'created_at', DB::raw("'shop' as source"))
            ->where('customer_id', $customer->id)
            ->where('payment_mode', 'Credit')
            ->where('payment_status', 'success')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->union($restaurantOrders)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalUsage = $orders->map(function ($item) {
            if ($item->source === 'restaurant') {
                return RestaurantOrder::where('id', $item->id)->first();
            } else {
                return ShopOrder::where('id', $item->id)->first();
            }
        })->sum('total_amount');

        return $customer->credit->amount - $totalUsage;
    }
}
