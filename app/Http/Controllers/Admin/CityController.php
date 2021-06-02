<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    use StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('cities', 'name', $request->by, $request->order);

        return City::with('townships')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $city = City::create($request->validate([
            'slug' => 'required|unique:cities',
            'name' => 'required|unique:cities',
        ]));

        return response()->json($city, 201);
    }

    public function show(City $city)
    {
        return response()->json($city->load('townships'), 200);
    }

    public function update(Request $request, City $city)
    {
        $city->update($request->validate([
            'name' => [
                'required',
                Rule::unique('cities')->ignore($city->id),
            ],
        ]));

        return response()->json($city, 200);
    }

    public function destroy(City $city)
    {
        $city->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }
}
