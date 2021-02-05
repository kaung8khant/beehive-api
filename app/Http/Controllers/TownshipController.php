<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Township;
use Illuminate\Http\Request;

class TownshipController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($filter)
    {
        return Township::with('city')
        ->where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)
        ->orderBy('name', 'desc')->paginate(10);
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

        $township = Township::create($request->validate([
            'name' => 'required|unique:townships',
            'slug' => 'required|unique:townships',
            'city_id' => 'required|exists:App\Models\City,id'
        ]));

        return response()->json($township, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Township  $township
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return Township::with('city')->where('slug', $slug)->firstOrFail();
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Township  $township
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Township $township)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Township  $township
     * @return \Illuminate\Http\Response
     */
    public function destroy(Township $township)
    {
        //
    }
}
