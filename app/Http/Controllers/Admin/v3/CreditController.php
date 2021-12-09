<?php

namespace App\Http\Controllers\Admin\v3;

use App\Events\DataChanged;
use App\Helpers\v3\OrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if ($credit) {
            $credit->remaining_amount = OrderHelper::getRemainingCredit($customer->id);
            return $credit;
        }

        return [];
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
}
