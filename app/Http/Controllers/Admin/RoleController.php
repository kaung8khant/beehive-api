<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    use StringHelper;

    public function index()
    {
        return Role::orderBy('name', 'asc')->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $role = Role::create($request->validate([
            'name' => 'required|unique:roles',
            'slug' => 'required|unique:roles',
        ]));

        return response()->json($role, 201);
    }

    public function show(Role $role)
    {
        return $role;
    }

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

    public function destroy(Role $role)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $role->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
