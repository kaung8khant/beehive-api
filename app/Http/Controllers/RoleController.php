<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    use StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/roles",
     *      operationId="getRoleLists",
     *      tags={"Roles"},
     *      summary="Get list of roles",
     *      description="Returns list of roles",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
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
    public function index()
    {
        return Role::orderBy('name', 'asc')->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/roles",
     *      operationId="storeRole",
     *      tags={"Roles"},
     *      summary="Create a role",
     *      description="Returns newly created role",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created role object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Role")
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

        $role = Role::create($request->validate([
            'name' => 'required|unique:roles',
            'slug' => 'required|unique:roles',
        ]));

        return response()->json($role, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/roles/{slug}",
     *      operationId="showRole",
     *      tags={"Roles"},
     *      summary="Get One Role",
     *      description="Returns a requested role",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested role",
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
    public function show(Role $role)
    {
        return $role;
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/roles/{slug}",
     *      operationId="updateRole",
     *      tags={"Roles"},
     *      summary="Update a Role",
     *      description="Update a requested role",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a role",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New role data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Role")
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
    public function update(Request $request, Role $role)
    {
        $role->update($request->validate([
            'name' => [
                'required',
                Rule::unique('roles')->ignore($role->id),
            ],
        ]));

        return response()->json($role, 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/roles/{slug}",
     *      operationId="showRole",
     *      tags={"Roles"},
     *      summary="Delete One Role",
     *      description="Delete one specific role",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested role",
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
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
