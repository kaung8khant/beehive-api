<?php

namespace App\Http\Controllers\Group;

use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Propaganistas\LaravelPhone\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerGroupController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        return CustomerGroup::with('customers')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('description', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $group = CustomerGroup::create($this->validateGroup($request));
        return response()->json($group, 201);
    }

    public function show($slug)
    {
        return CustomerGroup::with('customers')->where('slug', $slug)->firstOrFail();
    }

    public function update(Request $request, $slug)
    {
        $group = $this->show($slug);
        $group->update($this->validateGroup($request, $group->id));
        return $group;
    }

    public function destroy($slug)
    {
        $this->show($slug)->delete();
        return response()->json(['message' => 'successfully deleted.'], 200);
    }

    public function addCustomersToGroup(Request $request, $slug)
    {
        $group = $this->show($slug);
        $this->validateCustomers($request);

        $customerIds = $this->getCustomerIds($request->customer_slugs);
        $group->customers()->detach($customerIds);
        $group->customers()->attach($customerIds);
        return response()->json(['message' => 'The selected customers have been added to the group.'], 200);
    }

    public function removeCustomersFromGroup(Request $request, $slug)
    {
        $group = $this->show($slug);
        $this->validateCustomers($request);

        $customerIds = $this->getCustomerIds($request->customer_slugs);
        $group->customers()->detach($customerIds);
        return response()->json(['message' => 'The selected customers have been removed from the group.'], 200);
    }

    private function validateGroup($request, $id = null)
    {
        $rules = [
            'slug' => ['required', 'unique:customer_groups'],
            'name' => ['required', 'string', 'max:200', 'unique:customer_groups'],
            'description' => ['nullable', 'string'],
        ];

        if ($id) {
            $rules['name'][3] = Rule::unique('customer_groups')->ignore($id);
            unset($rules['slug']);
        }

        return $request->validate($rules);
    }

    private function validateCustomers($request)
    {
        $request->validate([
            'customer_slugs' => 'required|array',
            'customer_slugs.*' => 'required|exists:App\Models\Customer,slug',
        ]);
    }

    private function getCustomerIds($slugs)
    {
        return collect($slugs)->map(function ($slug) {
            return Customer::where('slug', $slug)->value('id');
        });
    }

    public function getCustomersByGroup(Request $request, $slug)
    {
        return Customer::whereHas('customerGroups', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('email', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    public function import(Request $request)
    {
        $validatedData = $request->validate(
            [
                'customer_groups' => 'nullable|array',
                'customer_groups.*.name' => 'required|max:255',
                'customer_groups.*.description' => 'nullable|string',
            ],
        );

        foreach ($validatedData['customer_groups'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            CustomerGroup::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    public function importCustomerToGroup(Request $request, $slug)
    {
        $group = $this->show($slug);

        $validatedData = $request->validate(
            [
                'customers' => 'nullable|array',
                'customers.*.email' => 'nullable|email|unique:customers',
                'customers.*.name' => 'required|max:255',
                'customers.*.phone_number' => 'required|phone:MM|unique:customers',
                'customers.*.password' => 'nullable|string|min:6',
                'customers.*.gender' => 'nullable|in:Male,Female',
            ],
            [
                'customers*.phone_number.phone' => 'Invalid phone number.',
            ]
        );

        foreach ($validatedData['customers'] as $data) {
            $data['phone_number'] = PhoneNumber::make($data['phone_number'], 'MM');
            $password = $data['password'] ? $data['password'] : $this->generateRandomPassword();
            $data['password'] = Hash::make($password);
            $data['created_by'] = 'admin';
            $data['slug'] = $this->generateUniqueSlug();
            Customer::create($data);
            $customerId = $this->getCustomerIds($data->slug);
            $group->customers()->detach($customerId);
            $group->customers()->attach($customerId);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
