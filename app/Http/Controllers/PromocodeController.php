<?php

namespace App\Http\Controllers;

use App\Helpers\CollectionHelper;
use App\Helpers\PromocodeHelper;
use App\Helpers\StringHelper;
use App\Models\Customer;
use App\Models\Promocode;
use App\Models\PromocodeRule;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    use StringHelper, PromocodeHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/promocodes",
     *      operationId="getPromocodeLists",
     *      tags={"Promocodes"},
     *      summary="Get list of promocodes",
     *      description="Returns list of promocodes",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *        name="filter",
     *        description="Filter",
     *        required=false,
     *        in="query",
     *        @OA\Schema(
     *            type="string"
     *        ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('promocodes', 'id', $request->by ? $request->by : 'desc', $request->order);

        return Promocode::with('rules')
            ->where('code', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/promocodes",
     *      operationId="storePromocode",
     *      tags={"Promocodes"},
     *      summary="Create a promocode",
     *      description="Returns newly created promocode",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created promocode object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Promocode")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
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

    /**
     * @OA\Get(
     *      path="/api/v2/admin/promocodes/{slug}",
     *      operationId="showPromocode",
     *      tags={"Promocodes"},
     *      summary="Get One Promocode",
     *      description="Returns a requested promocode",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested promocode",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show(Promocode $promocode)
    {
        return response()->json($promocode->load('rules'), 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/promocodes/{slug}",
     *      operationId="updatePromocode",
     *      tags={"Promocodes"},
     *      summary="Update a promocode",
     *      description="Update a requested promocode",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a promocode",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New promocode data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Promocode")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function update(Request $request, Promocode $promocode)
    {
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
            'description' => 'nullable|string',
            'rules' => 'nullable|array',
            'rules.*.value' => 'required|string',
            'rules.*.data_type' => 'required|in:before_date,after_date,exact_date,total_usage,per_user_usage,matching',
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

    /**
     * @OA\Post(
     *      path="/api/v2/admin/promocodes/add-rules/{slug}",
     *      operationId="addRules",
     *      tags={"Promocodes"},
     *      summary="Add Rules",
     *      description="Returns newly add rules data",
     *      @OA\Parameter(
     *      name="slug",
     *      description="Slug of a requested promocode",
     *      required=true,
     *      in="path",
     *      @OA\Schema(
     *         type="string"
     *       )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="add  rules in promocode object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(
     *                  @OA\Property(property="rules", type="array", @OA\Items(oneOf={
     *                      @OA\Schema(
     *                          @OA\Property(property="value", type="string", example="value"),
     *                          @OA\Property(property="data_type", type="string", example="before date"),
     *                          ),
     *                      })),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function addRules(Request $request, Promocode $promocode)
    {
        $validatedData = $request->validate([
            'rules' => 'required|array',
            'rules.*.value' => 'required|string',
            'rules.*.data_type' => 'required|in:before_date,after_date,exact_date,total_usage,per_user_usage,matching',
        ]);

        $promocode->rules()->delete();

        foreach ($validatedData['rules'] as $rule) {
            $rule['promocode_id'] = $promocode->id;
            PromocodeRule::create($rule);
        }

        return response()->json($promocode, 201);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/rules/{id}",
     *      operationId="removeRule",
     *      tags={"Promocodes"},
     *      summary="Remove Rule",
     *      description="Remove Rule",
     *      @OA\Parameter(
     *          name="id",
     *          description="id of a requested rule",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function removeRule(PromocodeRule $promocodeRule)
    {
        $promocodeRule->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/promocodes/{slug}",
     *      operationId="deletePromocode",
     *      tags={"Promocodes"},
     *      summary="Delete One Promocode",
     *      description="Delete one specific promocode",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested promocode",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function destroy(Promocode $promocode)
    {
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
