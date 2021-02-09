<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Role::paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * Display the specified resource.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return Role::where('slug', $slug)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $role = Role::where('slug', $slug)->firstOrFail();

        $role->update($request->validate([
            'name' => [
                'required',
                Rule::unique('roles')->ignore($role->id),
            ],
        ]));

        return response()->json($role, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Role::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
