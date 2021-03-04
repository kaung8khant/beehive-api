<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Promocode;
use App\Models\PromocodeRule;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Promocode::where('code', 'LIKE', '%' . $request->filter . '%')
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

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $promocode = Promocode::create($validatedData);
        $promocodeId = $promocode->id;

        $this->createRules($promocodeId, $validatedData['rules']);

        return response()->json($promocode, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(Promocode::with('rules')->where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $promocode = Promocode::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $promocode->update($validatedData);
        $this->createAndUpdateRules($promocode, $validatedData['rules']);
        return response()->json($promocode, 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'code' => 'required',
            'type' => 'required|in:fix,percentage',
            'usage' => 'required|in:restaurant,shop,both',
            'amount' => 'required|numeric',
            'rules' => 'required|array',
            'rules.*.name' => 'required|string',
            'rules.*.value' => 'required|string',
            'rules.*.data_type' => 'required|in:happy hour,date period,birthday,user limit',
            'rules.*.operator' => 'required|in:equal,less than,greater than,less than equal,greater than equal',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:promocodes';
        }

        return $params;
    }

    private function createRules($promocodeId, $rules)
    {
        foreach ($rules as $rule) {
            $rule['promocode_id'] = $promocodeId;
            PromocodeRule::create($rule);
        }
    }

    private function createAndUpdateRules($promocode, $rules)
    {
        $promocode->rules()->delete();
        foreach ($rules as $rule) {
            $rule['promocode_id'] = $promocode->id;
            PromocodeRule::create($rule);
        }
    }

    public function addRules(Request $request, $slug)
    {
        $validatedData = $request->validate([
            'rules' => 'required|array',
            'rules.*.name' => 'required|string',
            'rules.*.value' => 'required|string',
            'rules.*.data_type' => 'required|in:happy hour,date period,birthday,user limit',
            'rules.*.operator' => 'required|in:equal,less than,greater than,less than equal,greater than equal',
        ]);

        $promocode = Promocode::where('slug', $slug)->firstOrFail();
        $promocode->rules()->delete();
        foreach ($validatedData['rules'] as $rule) {
            $rule['promocode_id'] = $promocode->id;
            PromocodeRule::create($rule);
        }

        return response()->json($promocode, 201);
    }

    public function removeRule($slug)
    {
        PromocodeRule::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Promocode  $promocode
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        Promocode::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }
}
