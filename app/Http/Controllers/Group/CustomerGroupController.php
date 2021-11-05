<?php

namespace App\Http\Controllers\Group;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerGroupController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $customerGroups = CustomerGroup::where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('description', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);

        return CollectionHelper::removePaginateLinks($customerGroups);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();
        $group = CustomerGroup::create($this->validateGroup($request));
        return response()->json($group, 201);
    }

    public function show(CustomerGroup $customerGroup)
    {
        return $customerGroup;
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $customerGroup->update($this->validateGroup($request, $customerGroup->id));
        return $customerGroup;
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        $customerGroup->delete();
        return response()->json(['message' => 'successfully deleted.'], 200);
    }

    public function addCustomersToGroup(Request $request, CustomerGroup $customerGroup)
    {
        $this->validateCustomers($request);

        $customerIds = $this->getCustomerIds($request->customer_slugs);
        $customerGroup->customers()->detach($customerIds);
        $customerGroup->customers()->attach($customerIds);

        return response()->json(['message' => 'The selected customers have been added to the group.'], 200);
    }

    public function removeCustomersFromGroup(Request $request, CustomerGroup $customerGroup)
    {
        $this->validateCustomers($request);

        $customerIds = $this->getCustomerIds($request->customer_slugs);
        $customerGroup->customers()->detach($customerIds);

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
}
