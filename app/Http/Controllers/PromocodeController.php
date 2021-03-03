<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromocodeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Promocode::orWhere('code', $request->filter)
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
        $promocode = Promocode::create($request->validate(
            [
                'code' => 'required|unique:promocodes',
                'type' => 'required|in:fix,percentage',
                'usage' => 'required|in:restaurant,shop,both',
                'amount' => 'required|numeric',
            ]
        ));

        return response()->json($promocode, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        return response()->json(Promocode::where('code', $code)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $code)
    {
        $promocode = Promocode::where('code', $code)->firstOrFail();

        $validatedData = $request->validate([
            'code' => [
                'required',
                Rule::unique('promocodes')->ignore($promocode->id),
            ],
            'type' => 'required|in:fix,percentage',
            'usage' => 'required|in:restaurant,shop,both',
            'amount' => 'required|numeric',
        ]);

        $promocode->update($validatedData);
        return response()->json($promocode, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        Promocode::where('code', $code)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
