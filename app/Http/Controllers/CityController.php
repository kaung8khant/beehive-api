<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\City;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class CityController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return City::paginate(10);
        // return City::with('townships')
        // ->where('name', 'LIKE', '%' . $name . '%')
        // ->orWhere('slug', $name)->paginate(10);
    }

    public function search($filter)
    {
        return City::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug']=$this->generateUniqueSlug();

        $city=City::create($request->validate(
            [
                'name'=>'required|unique:cities',
                'name_mm'=>'unique:cities',
                'slug'=>'required|unique:cities',
            ]
        ));
        return response()->json($city, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\City  $city
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(City::with('townships')->where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\City  $city
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $city=City::where('slug', $slug)->firstOrFail();

        $city->update($request->validate([
            'name'=>'required',
            'name_mm'=>'unique:cities',
            Rule::unique('cities')->ignore($city->id),
        ]));

        return response()->json($city, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\City  $city
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        City::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}