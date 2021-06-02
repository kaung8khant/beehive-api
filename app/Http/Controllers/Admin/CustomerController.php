<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Promocode;
use App\Models\RestaurantOrder;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class CustomerController extends Controller
{
    use StringHelper, CollectionHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('customers', 'name', $request->by, $request->order);

        return Customer::where('email', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate(
            [
                'slug' => 'required|unique:customers',
                'email' => 'nullable|email|unique:customers',
                'name' => 'required|max:255',
                'phone_number' => 'required|phone:MM|unique:customers',
                'password' => 'nullable|string|min:6',
                'gender' => 'required|in:Male,Female',
                'customer_groups' => 'nullable|array',
                'customer_groups.*' => 'exists:App\Models\CustomerGroup,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $password = $validatedData['password'] ? $validatedData['password'] : $this->generateRandomPassword();

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $validatedData['password'] = Hash::make($password);
        $validatedData['created_by'] = 'admin';

        $customer = Customer::create($validatedData);

        if ($request->customer_groups) {
            $customerGroups = CustomerGroup::whereIn('slug', $request->customer_groups)->pluck('id');
            $customer->customerGroups()->attach($customerGroups);
        }

        return response()->json($customer->load('customerGroups'), 201);
    }

    public function show(Customer $customer)
    {
        return $customer->load(['addresses', 'customerGroups']);
    }

    public function update(Request $request, Customer $customer)
    {
        $validatedData = $request->validate(
            [
                'name' => 'required',
                'phone_number' => [
                    'required',
                    'phone:MM',
                    Rule::unique('customers')->ignore($customer->id),
                ],
                'gender' => 'required|in:Male,Female',
                'customer_groups' => 'nullable|array',
                'customer_groups.*' => 'exists:App\Models\CustomerGroup,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $validatedData['customer_groups'] = isset($validatedData['customer_groups']) ? $validatedData['customer_groups'] : [];

        $customerGroups = CustomerGroup::whereIn('slug', $validatedData['customer_groups'])->pluck('id');
        $customer->customerGroups()->detach();
        $customer->customerGroups()->attach($customerGroups);

        $customer->update($validatedData);
        return response()->json($customer->load('customerGroups'), 200);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable(Customer $customer)
    {
        $customer->update(['is_enable' => !$customer->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function getPromocodeUsedCustomers(Request $request, Promocode $promocode)
    {
        $shopOrder = ShopOrder::where('promocode_id', $promocode->id)->get();
        $restaurantOrder = RestaurantOrder::where('promocode_id', $promocode->id)->get();

        $orderList = $shopOrder->merge($restaurantOrder);

        $customerlist = [];

        foreach ($orderList as $order) {
            $customer = Customer::where('id', $order->customer_id)->where(function ($query) use ($request) {
                $query->where('email', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })->first();
            $customer && array_push($customerlist, $customer);
        }

        $customerlist = collect($customerlist)->unique()->values()->all();
        $customerlist = CollectionHelper::paginate(collect($customerlist), $request->size);

        return response()->json($customerlist, 200);
    }
}
