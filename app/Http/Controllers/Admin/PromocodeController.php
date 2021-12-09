<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CollectionHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\PromocodeRule;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    use StringHelper, PromocodeHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('promocodes', 'id', $request->by ? $request->by : 'desc', $request->order);

        return Promocode::with('rules')
            ->where('code', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $promocode = Promocode::create($validatedData);

        if (isset($validatedData['rules'])) {
            $this->createRules($promocode->id, $validatedData['rules']);
        }

        return response()->json($promocode, 201);
    }

    public function show(Promocode $promocode)
    {
        return response()->json($promocode->load('rules'), 200);
    }

    public function update(Request $request, Promocode $promocode)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        foreach ($validatedData['rules'] as $key => $value) {
            $value = is_string($value['value']) ? $value['value'] : json_encode($value['value']);
            $validatedData['rules'][$key]['value'] = $value;
        }

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
            'description' => 'nullable|string',
            'rules' => 'nullable|array',
            'rules.*.value' => 'required|string_or_array',
            'rules.*.data_type' => 'required|string',
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
            $rule['value'] = is_string($rule['value']) ? $rule['value'] : json_encode($rule['value']);
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

    public function addRules(Request $request, Promocode $promocode)
    {
        $validatedData = $request->validate([
            'rules' => 'required|array',
            'rules.*.value' => 'required|string_or_array',
            'rules.*.data_type' => 'required|string',
        ]);

        $promocode->rules()->delete();

        foreach ($validatedData['rules'] as $rule) {
            $rule['promocode_id'] = $promocode->id;
            $rule['value'] = is_string($rule['value']) ? $rule['value'] : json_encode($rule['value']);
            PromocodeRule::create($rule);
        }

        return response()->json($promocode, 201);
    }

    public function removeRule(PromocodeRule $promocodeRule)
    {
        $promocodeRule->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function destroy(Promocode $promocode)
    {
        return response()->json(['message' => 'Permission denied.'], 403);

        $promocode->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function validateCode(Request $request, $slug)
    {
        $validatedData = $request->validate([
            'customer_slug' => 'required|string',
            'usage' => 'required|string',
        ]);

        $customer = Customer::where('slug', $validatedData['customer_slug'])->firstOrFail();
        $isPromoValid = $this->validatePromo($slug, $customer->id, $validatedData['usage']);

        if (!$isPromoValid) {
            return $this->generateResponse('Invalid promo code.', 406, true);
        }

        return $this->generateResponse('Promo code is valid', 200, true);
    }
}
