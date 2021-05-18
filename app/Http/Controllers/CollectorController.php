<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;

class CollectorController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/collectors",
     *      operationId="getCollectorLists",
     *      tags={"Collectors"},
     *      summary="Get list of collectors",
     *      description="Returns list of collectors",
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
        $sorting = CollectionHelper::getSorting('users', 'name', $request->by, $request->order);

        return User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'Collector');
            })
            ->where(function ($q) use ($request) {
                $q->where('username', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/collectors",
     *      operationId="storeUser",
     *      tags={"Collectors"},
     *      summary="Create a collector",
     *      description="Returns newly created collector",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created collector object",
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

        $collector = User::create($validatedData);

        $collectorRoleId = Role::where('name', 'Collector')->first()->id;
        $collector->roles()->attach($collectorRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $collector->slug);
        }
        return response()->json($collector->refresh()->load('roles'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/collectors/{slug}",
     *      operationId="showCollector",
     *      tags={"Collectors"},
     *      summary="Get One collector",
     *      description="Returns a requested collector",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested collector",
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
     *      path="/api/v2/admin/collectors/{slug}",
     *      operationId="updateCollector",
     *      tags={"Collectors"},
     *      summary="Update a collector",
     *      description="Update a requested collector",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a collector",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New collector data to be updated.",
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
        $collector = User::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate(
            [
                'username' => [
                    'required',
                    Rule::unique('users')->ignore($collector->id),
                ],
                'name' => 'required',
                'phone_number' => [
                    'required',
                    'phone:MM',
                    Rule::unique('users')->ignore($collector->id),
                ],
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ],
            [
                'phone_number.phone' => 'Invalid phone number.',
            ]
        );

        $validatedData['phone_number'] = PhoneNumber::make($validatedData['phone_number'], 'MM');

        $collector->update($validatedData);

        $collectorRoleId = Role::where('name', 'Collector')->first()->id;
        $collector->roles()->detach();
        $collector->roles()->attach($collectorRoleId);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'users', $collector->slug);
        }
        return response()->json($collector->refresh()->load('roles'), 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/collectors/{slug}",
     *      operationId="deleteCollector",
     *      tags={"Collectors"},
     *      summary="Delete One collector",
     *      description="Delete one specific collector",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested collector",
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
        $collector = User::where('slug', $slug)->firstOrFail();

        foreach ($collector->images as $image) {
            $this->deleteFile($image->slug);
        }

        if ($collector->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        $collector->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * @OA\Patch(
     *      path="/api/v2/admin/collectors/toggle-enable/{slug}",
     *      operationId="enableCollector",
     *      tags={"Collectors"},
     *      summary="Enable collector",
     *      description="Enable a collector",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the collector",
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
    public function toggleEnable(User $user)
    {
        if ($user->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot change your own status.'], 406);
        }

        $user->update(['is_enable' => !$user->is_enable]);
        return response()->json(['message' => 'Success.'], 200);
    }
}
