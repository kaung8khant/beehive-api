<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
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
    public function index(Request $request)
    {
        return Township::with('city')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
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
            'name_mm' => 'unique:townships',
            'slug' => 'required|unique:townships',
            'city_id' => 'required|exists:App\Models\City,id'
        ]));

        return response()->json($township, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $township = Township::with('city')->where('slug', $slug)->firstOrFail();
        return response()->json($township, 200);
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
        $township = Township::where('slug', $slug)->firstOrFail();

        $township->update($request->validate([
            'name' => [
                'required',
                Rule::unique('townships')->ignore($township->id),
            ],
            'name_mm' => [
                Rule::unique('townships')->ignore($township->id),
            ],
            'city_id' => 'required|exists:App\Models\City,id',
        ]));

        return response()->json($township, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Township::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * Display a listing of the townships by a city.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
    public function getTownshipsByCity($slug)
    {
        return Township::whereHas('city', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->paginate(10);
    }
}
