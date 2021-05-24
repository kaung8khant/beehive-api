<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Models\City;
use App\Models\Township;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TownshipController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('townships', 'name', $request->by, $request->order);

        return Township::with('city')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate([
            'slug' => 'required|unique:townships',
            'name' => 'required|unique:townships',
            'city_slug' => 'required|exists:App\Models\City,slug',
        ]);

        $validatedData['city_id'] = $this->getCityIdBySlug($request->city_slug);
        $township = Township::create($validatedData);

        return response()->json($township->load('city'), 201);
    }

    public function show(Township $township)
    {
        return response()->json($township->load('city'), 200);
    }

    public function update(Request $request, Township $township)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('townships')->ignore($township->id),
            ],
            'city_slug' => 'required|exists:App\Models\City,slug',
        ]);

        $validatedData['city_id'] = $this->getCityIdBySlug($request->city_slug);
        $township->update($validatedData);

        return response()->json($township->load('city'), 200);
    }

    public function destroy(Township $township)
    {
        $township->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getCityIdBySlug($slug)
    {
        return City::where('slug', $slug)->first()->id;
    }

    public function getTownshipsByCity(Request $request, City $city)
    {
        $sorting = CollectionHelper::getSorting('townships', 'name', $request->by, $request->order);

        return Township::where('city_id', $city->id)
            ->where(function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }
}
