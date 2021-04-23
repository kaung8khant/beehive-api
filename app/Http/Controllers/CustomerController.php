<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Models\Customer;
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

    /**
     * @OA\Get(
     *      path="/api/v2/admin/customers",
     *      operationId="getCustomerLists",
     *      tags={"Customer"},
     *      summary="Get list of customers",
     *      description="Returns list of customers",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *        name="filter",
     *        description="Filter",
     *        required=false,
     *        in="query",
     *        @OA\Schema(
     *            type="string"
     *        ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index(Request $request)
    {
        return Customer::where('email', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/customers",
     *      operationId="storeCustomer",
     *      tags={"Customer"},
     *      summary="Create a Customer",
     *      description="Returns newly created Customer",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created Customer object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Customer")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate(
            [
                'slug' => 'required|unique:customers',
                'email' => 'nullable|email|unique:customers',
                'name' => 'required|max:255',
                'phone_number' => 'required|phone:MM|unique:customers',
                'password' => 'required|string|min:6',
                'gender' => 'required|in:Male,Female',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $validatedData['password'] = Hash::make($validatedData['password']);

        $customer = Customer::create($validatedData);
        return response()->json($customer->refresh(), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/customers/{slug}",
     *      operationId="showCustomer",
     *      tags={"Customer"},
     *      summary="Get One Customer",
     *      description="Returns a requested Customer",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested Customer",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show($slug)
    {
        return Customer::with('addresses')->where('slug', $slug)->firstOrFail();
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/customers/{slug}",
     *      operationId="updateCustomer",
     *      tags={"Customer"},
     *      summary="Update a Customer",
     *      description="Update a requested Customer",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a Customer",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New Customer data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Customer")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function update(Request $request, $slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => 'required',
            'phone_number' => [
                'required',
                Rule::unique('customers')->ignore($customer->id),
            ],
            'gender' => 'required|in:Male,Female',
        ]);

        $customer->update($validatedData);
        return response()->json($customer, 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/customers/{slug}",
     *      operationId="showCustomer",
     *      tags={"Customer"},
     *      summary="Delete One Customer",
     *      description="Delete one specific Customer",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested Customer",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function destroy($slug)
    {
        Customer::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/customers/toggle-enable/{slug}",
     *      operationId="enableCustomer",
     *      tags={"Customer"},
     *      summary="Enable customer",
     *      description="Enable a customer",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the customer",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function toggleEnable($slug)
    {
        $customer = Customer::where('slug', $slug)->firstOrFail();
        $customer->is_enable = !$customer->is_enable;
        $customer->save();
        return response()->json(['message' => 'Success.'], 200);
    }

    public function getPromocodeUsedCustomers(Request $request, $slug)
    {
        $promocode = Promocode::where('slug', $slug)->firstOrFail();

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

        return response()->json(['data' => $customerlist], 200);
    }
}
