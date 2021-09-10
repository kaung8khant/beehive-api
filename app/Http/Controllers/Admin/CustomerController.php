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
use Propaganistas\LaravelPhone\PhoneNumber;

class CustomerController extends Controller
{
    use StringHelper, CollectionHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('customers', 'name', $request->by, $request->order);

        return Customer::with('customerGroups')->where('email', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateCustomer($request, true);

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $checkPhone = Customer::where('phone_number', $validatedData['phone_number'])->first();

        if ($checkPhone) {
            return [
                'message' => 'The given data was invalid.',
                'errors' => [
                    'phone_number' => [
                        'The phone number has already been taken.',
                    ],
                ],
            ];
        }

        $password = $validatedData['password'] ? $validatedData['password'] : $this->generateRandomPassword();
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
                'phone_number' => 'required|phone:MM',
                'gender' => 'required|in:Male,Female',
                'customer_groups' => 'nullable|array',
                'customer_groups.*' => 'exists:App\Models\CustomerGroup,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $checkPhone = Customer::where('phone_number', $validatedData['phone_number'])->where('id', '<>', $customer->id)->first();

        if ($checkPhone) {
            return [
                'message' => 'The given data was invalid.',
                'errors' => [
                    'phone_number' => [
                        'The phone number has already been taken.',
                    ],
                ],
            ];
        }

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

    private function validateCustomer($request, $slug = false)
    {
        $rules = [
            'email' => 'nullable|email|unique:customers',
            'name' => 'required|max:255',
            'phone_number' => 'required|phone:MM',
            'password' => 'nullable|string|min:6',
            'gender' => 'required|in:Male,Female',
            'customer_groups' => 'nullable|array',
            'customer_groups.*' => 'exists:App\Models\CustomerGroup,slug',
        ];

        $messages = [
            'phone_number.phone' => 'Invalid phone number.',
        ];

        if ($slug) {
            $request['slug'] = $this->generateUniqueSlug();
            $rules['slug'] = 'required|unique:customers';
        }

        return $request->validate($rules, $messages);
    }

    public function toggleEnable(Customer $customer)
    {
        $customer->update(['is_enable' => !$customer->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }

    public function getPromocodeUsedCustomerCounts(Request $request, Promocode $promocode)
    {
        $shopOrders = ShopOrder::where('promocode_id', $promocode->id)->select('customer_id')
            ->get();
        $restaurantOrders = RestaurantOrder::where('promocode_id', $promocode->id)->select('customer_id')
            ->get();

        $totalFrequency = 0;

        $orderList = collect($shopOrders)->merge($restaurantOrders)->groupBy('customer_id');
        foreach ($orderList as $key => $group) {
            $totalFrequency+=$group->count();
        }
        $result = [
            'total_frequency' => $totalFrequency,
            'total_user_count' => $orderList->count(),
        ];
        return $result;
    }


    public function getPromocodeUsedCustomers(Request $request, Promocode $promocode)
    {
        $shopOrders = ShopOrder::where('promocode_id', $promocode->id)->select('customer_id')
            ->get();
        $restaurantOrders = RestaurantOrder::where('promocode_id', $promocode->id)->select('customer_id')
            ->get();

        $orderList = collect($shopOrders)->merge($restaurantOrders)->groupBy('customer_id');

        $customerlist = [];

        foreach ($orderList as $key => $group) {
            $customer = Customer::where('id', $group[0]->customer_id)->where(function ($query) use ($request) {
                $query->where('email', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })->first();
            $customer && array_push($customerlist, [
                'frequency' =>$group->count(),
                'slug'=>$customer->slug,
                'name'=>$customer->name,
                'email'=>$customer->email,
                'phone_number'=>$customer->phone_number,
            ]);
        }

        $customerlist = CollectionHelper::paginate(collect($customerlist), $request->size);

        return response()->json($customerlist, 200);
    }

    public function getOrdersByCustomer(Request $request, $slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();

        $shopOrder = ShopOrder::with('contact')
            ->where('customer_id', $customer->id)
            ->get()
            ->map(function ($shopOrder) {
                return $shopOrder->makeHidden('vendors');
            });

        $restaurantOrder = RestaurantOrder::with('restaurantOrderContact')
            ->where('customer_id', $customer->id)
            ->get();
        if ($request->filter) {
            $orderList = $shopOrder->merge($restaurantOrder)->where('id', ltrim($request->filter, '0'));
        } else {
            $orderList = $shopOrder->merge($restaurantOrder);
        }
        $orderList = CollectionHelper::paginate(collect($orderList), $request->size);

        return response()->json($orderList, 200);
    }
}
