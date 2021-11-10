<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Helpers\v3\OrderHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\RestaurantBranch;
use App\Models\RestaurantOrder;
use App\Models\Shop;
use App\Models\ShopOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Propaganistas\LaravelPhone\PhoneNumber;

class CustomerController extends Controller
{
    use StringHelper, CollectionHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('customers', 'name', $request->by, $request->order);

        $customers = Customer::where('email', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->with([
                'credit' => function ($query) {
                    $query->exclude(['created_by', 'updated_by']);
                },
                'customerGroups' => function ($query) {
                    $query->exclude(['created_by', 'updated_by']);
                },
            ])
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);

        $this->optimizeCustomers($customers);

        foreach ($customers as $customer) {
            if ($customer->credit) {
                $customer->credit['remaining_amount'] = OrderHelper::getRemainingCredit($customer);
            }
        }

        return CollectionHelper::removePaginateLinks($customers);
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateCustomer($request, true);

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $checkPhone = Customer::where('phone_number', $validatedData['phone_number'])->first();

        if ($checkPhone) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'phone_number' => [
                        'The phone number has already been taken.',
                    ],
                ],
            ], 422);
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
        $customer = $customer->load([
            'addresses',
            'customerGroups',
            'credit' => function ($query) {
                $query->exclude(['created_by', 'updated_by']);
            },
        ]);

        if ($customer->credit) {
            $customer->credit['remaining_amount'] = OrderHelper::getRemainingCredit($customer);
        }

        return $customer;
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
        return response()->json(['message' => 'Permission denied.'], 403);

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

    public function getOrdersByCustomer(Request $request, Customer $customer)
    {
        $shopOrder = DB::table('shop_orders')->where('customer_id', $customer->id)->select('id', 'created_at');
        $restaurantOrder = DB::table('restaurant_orders')->where('customer_id', $customer->id)->select('id', 'created_at');

        if ($request->filter) {
            $shopOrder = $shopOrder->where('id', ltrim(ltrim($request->filter, 'BHS'), '0'));
            $restaurantOrder = $restaurantOrder->where('id', ltrim(ltrim($request->filter, 'BHR'), '0'));
        }

        $shopOrder = $shopOrder->get()->map(function ($shop) {
            $shop->type = 'shop';
            return $shop;
        });

        $restaurantOrder = $restaurantOrder->get()->map(function ($restaurant) {
            $restaurant->type = 'restaurant';
            return $restaurant;
        });

        $orders = $shopOrder->merge($restaurantOrder)->sortByDesc('created_at');
        $orders = CollectionHelper::paginate($orders, $request->size);
        $orders = CollectionHelper::removePaginateLinks($orders);

        $orders['data'] = collect($orders['data'])->map(function ($item) {
            if ($item->type === 'shop') {
                return ShopOrder::where('id', $item->id)
                    ->exclude(['delivery_mode', 'promocode', 'promocode_amount', 'promocode_id', 'customer_id', 'special_instruction', 'created_by', 'updated_by'])
                    ->first()
                    ->makeHidden(['vendors']);
            } else {
                return RestaurantOrder::where('id', $item->id)
                    ->exclude(['delivery_mode', 'promocode', 'promocode_amount', 'promocode_id', 'customer_id', 'special_instruction', 'created_by', 'updated_by'])
                    ->first()
                    ->makeHidden(['restaurant_branch_info', 'restaurantOrderItems', 'driver_status']);
            }
        });

        return $orders;
    }

    public function getCustomersByShop(Request $request, Shop $shop)
    {
        $customerIds = ShopOrder::whereHas('vendors', function ($query) use ($shop) {
            $query->where('shop_id', $shop->id);
        })->pluck('customer_id')->filter()->unique()->values();

        $customers = Customer::whereIn('id', $customerIds)
            ->where(function ($query) use ($request) {
                $query->where('email', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy('name')
            ->paginate($request->size);

        $this->optimizeCustomers($customers);
        return CollectionHelper::removePaginateLinks($customers);
    }

    public function getCustomersByBranch(Request $request, RestaurantBranch $restaurantBranch)
    {
        $customerIds = RestaurantOrder::where('restaurant_branch_id', $restaurantBranch->id)->pluck('customer_id')->filter()->unique()->values();

        $customers = Customer::whereIn('id', $customerIds)
            ->where(function ($query) use ($request) {
                $query->where('email', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy('name')
            ->paginate($request->size);

        $this->optimizeCustomers($customers);
        return CollectionHelper::removePaginateLinks($customers);
    }

    public function getCustomersByGroup(Request $request, CustomerGroup $customerGroup)
    {
        $customers = Customer::whereHas('customerGroups', function ($query) use ($customerGroup) {
            $query->where('slug', $customerGroup->slug);
        })
            ->where(function ($query) use ($request) {
                $query->where('email', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);

        $this->optimizeCustomers($customers);
        return CollectionHelper::removePaginateLinks($customers);
    }

    private function optimizeCustomers($customers)
    {
        $customers->makeHidden(['id', 'device_token', 'primary_address']);
    }
}
