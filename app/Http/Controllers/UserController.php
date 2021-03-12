<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/users",
     *      operationId="getUserLists",
     *      tags={"Users"},
     *      summary="Get list of users",
     *      description="Returns list of users",
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
                $q->whereNotIn('name', ['Driver', 'Collector']);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *      path="/api/v2/admin/users",
     *      operationId="storeUser",
     *      tags={"Users"},
     *      summary="Create a user",
     *      description="Returns newly created user",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created user object",
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

        $validatedData = $request->validate([
            'slug' => 'required|unique:users',
            'username' => 'required|unique:users',
            'name' => 'required',
            'phone_number' => 'required|unique:users',
            'password' => 'required|min:6',
            'roles' => 'required|array',
            'roles.*' => 'exists:App\Models\Role,slug',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);
        $user = User::create($validatedData);

        $roles = Role::whereIn('slug', $request->roles)->pluck('id');
        $user->roles()->attach($roles);

        return response()->json($user->refresh()->load('roles'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/users/{slug}",
     *      operationId="showUser",
     *      tags={"Users"},
     *      summary="Get One user",
     *      description="Returns a requested user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested user",
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *      path="/api/v2/admin/users/{slug}",
     *      operationId="updateUser",
     *      tags={"Users"},
     *      summary="Update a user",
     *      description="Update a requested user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a user",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New user data to be updated.",
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
        $user = User::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'username' => [
                'required',
                Rule::unique('users')->ignore($user->id),
            ],
            'name' => 'required',
            'phone_number' => [
                'required',
                Rule::unique('users')->ignore($user->id),
            ],
            'roles' => 'required|array',
            'roles.*' => 'exists:App\Models\Role,slug',
        ]);

        $user->update($validatedData);

        $roles = Role::whereIn('slug', $request->roles)->pluck('id');
        $user->roles()->detach();
        $user->roles()->attach($roles);

        return response()->json($user->load('roles'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     *      path="/api/v2/admin/users/{slug}",
     *      operationId="deleteUser",
     *      tags={"Users"},
     *      summary="Delete One User",
     *      description="Delete one specific user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested user",
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
        $user = User::where('slug', $slug)->firstOrFail();

        if ($user->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 406);
        }

        $user->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * Toggle the is_enable column for users table.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Patch(
     *      path="/api/v2/admin/users/toggle-enable/{slug}",
     *      operationId="enableUser",
     *      tags={"Users"},
     *      summary="Enable user",
     *      description="Enable a user",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of the user",
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
        $user = User::where('slug', $slug)->firstOrFail();

        if ($user->id === Auth::guard('users')->user()->id) {
            return response()->json(['message' => 'You cannot change your own status.'], 406);
        }

        $user->is_enable = !$user->is_enable;
        $user->save();
        return response()->json(['message' => 'Success.'], 200);
    }
}
