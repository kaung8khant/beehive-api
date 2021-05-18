<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class DriverController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/drivers",
     *      operationId="getDriverLists",
     *      tags={"Drivers"},
     *      summary="Get list of drivers",
     *      description="Returns list of drivers",
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
        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Driver');
            })
            ->where(function ($q) use ($request) {
                $q->where('username', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/drivers",
     *      operationId="storeDriver",
     *      tags={"Drivers"},
     *      summary="Create a driver",
     *      description="Returns newly created driver",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created driver object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/User")
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
                'slug' => 'required|unique:users',
                'username' => 'required|unique:users',
                'name' => 'required|string',
                'phone_number' => 'required|phone:MM|unique:users',
                'password' => 'required|min:6',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');
        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['created_by'] = Auth::guard('users')->user()->id;

        $driver = User::create($validatedData);

        $driverRoleId = Role::where('name', 'Driver')->first()->id;
        $driver->roles()->attach($driverRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $driver->slug);
        }
        return response()->json($driver->refresh()->load('roles'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/drivers/{slug}",
     *      operationId="showDriver",
     *      tags={"Drivers"},
     *      summary="Get One driver",
     *      description="Returns a requested driver",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested driver",
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
        return User::with('roles')->where('slug', $slug)->firstOrFail();
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/drivers/{slug}",
     *      operationId="updateDriver",
     *      tags={"Drivers"},
     *      summary="Update a driver",
     *      description="Update a requested driver",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a driver",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New driver data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/User")
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
        $driver = User::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate(
            [
                'username' => [
                    'required',
                    Rule::unique('users')->ignore($driver->id),
                ],
                'name' => 'required',
                'phone_number' => [
                    'required',
                    'phone:MM',
                    Rule::unique('users')->ignore($driver->id),
                ],
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');

        $driver->update($validatedData);

        $driverRoleId = Role::where('name', 'Driver')->first()->id;
        $driver->roles()->detach();
        $driver->roles()->attach($driverRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $driver->slug);
        }
        return response()->json($driver->refresh()->load('roles'), 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/drivers/{slug}",
     *      operationId="deleteDriver",
     *      tags={"Drivers"},
     *      summary="Delete One driver",
     *      description="Delete one specific driver",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested driver",
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
        $driver = User::where('slug', $slug)->firstOrFail();

        foreach ($driver->images as $image) {
            $this->deleteFile($image->slug);
        }

        if ($driver->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        $driver->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/drivers/toggle-enable/{slug}",
     *      operationId="enableDriver",
     *      tags={"Drivers"},
     *      summary="Enable driver",
     *      description="Enable a driver",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the driver",
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
        $driver = User::where('slug', $slug)->firstOrFail();

        if ($driver->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot change your own status.'], 406);
        }

        $driver->is_enable = !$driver->is_enable;
        $driver->save();
        return response()->json(['message' => 'Success.'], 200);
    }
}
