<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\City;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TownshipController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Township::with('city')->paginate(10);
        // return Township::with('city')
        // ->where('name', 'LIKE', '%' . $filter . '%')
        // ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        // ->orWhere('slug', $filter)
        // ->paginate(10);
    }

    public function search($filter)
    {
        return Township::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
    }

    public function getTownshipsByCity($slug)
    {
        $city=City::with('townships')->where('slug', $slug)->firstOrFail();
        return response()->json($city->townships()->paginate(10), 200);
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
            'name_mm'=>'unique:townships',
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
        return response()->json(Township::with('city')->where('slug', $slug)->firstOrFail(), 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Township  $township
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $township = Township::where('slug', $slug)->firstOrFail();

        $township->update($request->validate([
            'name' => ['required',
            Rule::unique('townships')->ignore('$township_id'),
        ],
            'name_mm'=>'unique:townships',
            'city_id' => 'required|exists:App\Models\City,id',
        ]));
        return response()->json($township, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Township  $township
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Township::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'Successfully deleted'], 200);
    }
}
