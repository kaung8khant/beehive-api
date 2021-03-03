<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Township;
use App\Models\City;

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

        $validatedData = $request->validate([
            'slug' => 'required|unique:townships',
            'name' => 'required|unique:townships',
            'name_mm' => 'nullable|unique:townships',
            'city_slug' => 'required|exists:App\Models\City,slug'
        ]);

        $validatedData['city_id'] = $this->getCityIdBySlug($request->city_slug);

        $township = Township::create($validatedData);
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

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('townships')->ignore($township->id),
            ],
            'name_mm' => [
                'nullable',
                Rule::unique('townships')->ignore($township->id),
            ],
            'city_slug' => 'required|exists:App\Models\City,slug',
        ]);

        $validatedData['city_id'] = $this->getCityIdBySlug($request->city_slug);
        $township->update($validatedData);

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

    private function getCityIdBySlug($slug)
    {
        return City::where('slug', $slug)->first()->id;
    }

    /**
    * Display a listing of the townships by one city.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  string  $slug
    * @return \Illuminate\Http\Response
    */
    public function getTownshipsByCity(Request $request, $slug)
    {
        return Township::whereHas('city', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter .'%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter);
        })->paginate(10);
    }
}
