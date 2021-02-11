<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Auth::guard('users')->user());
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_profile(Request $request)
    {
        $user = Auth::user();

        $user->update($request->validate([
            'name' => ['required',
            Rule::unique('users')->ignore($user->id),
            ],
            'username' => ['required',
            Rule::unique('users')->ignore($user->id),
            ],
            'phone_number' => ['required',
            Rule::unique('users')->ignore($user->id),
            ],
            'is_enable' => 'required|boolean:users',
            'is_locked' => 'required|boolean:users',
        ]));

        return response()->json($user, 200);
    }
}