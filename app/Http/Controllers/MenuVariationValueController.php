<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\MenuVariation;
use App\Models\MenuVariationValue;
use Illuminate\Http\Request;

class MenuVariationValueController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/menu-variation-values",
     *      operationId="getMenuVariationValueLists",
     *      tags={"Menu Variation values"},
     *      summary="Get list of menu variation value",
     *      description="Returns list of menu variation value",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="filter",
     *          description="Filter",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
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
        return MenuVariationValue::with('menuVariation')
            ->where('value', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/menu-variation-values",
     *      operationId="storeMenuVariationValue",
     *      tags={"Menu Variation values"},
     *      summary="Create a Menu Variation Value",
     *      description="Returns newly created menu variation value",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created menu variation value object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/MenuVariationValue")
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
        $validatedData['menu_variation_id'] = $this->getMenuVariationId($request->menu_variation_slug);

        $menuVariationValue = MenuVariationValue::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menu_variation_values', $menuVariationValue->slug);
        }

        return response()->json($menuVariationValue->load('menuVariation'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/menu-variation-values/{slug}",
     *      operationId="showMenuVariationValue",
     *      tags={"Menu Variation values"},
     *      summary="Get One menu variation value",
     *      description="Returns a requested menu variation value",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu variation value",
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
    public function show(MenuVariationValue $menuVariationValue)
    {
        return response()->json($menuVariationValue->load('menuVariation'), 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/menu-variation-values/{slug}",
     *      operationId="updateMenuVariationValue",
     *      tags={"Menu Variation values"},
     *      summary="Update a Menu Variation Value",
     *      description="Update a requested menu variation value",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a menu variation value",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New menu variation value data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/MenuVariationValue")
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
    public function update(Request $request, MenuVariationValue $menuVariationValue)
    {
        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_variation_id'] = $this->getMenuVariationId($request->menu_variation_slug);
        $menuVariationValue->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menu_variation_values', $menuVariationValue->slug);
        }

        return response()->json($menuVariationValue->load('menuVariation'), 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/menu-variation-values/{slug}",
     *      operationId="deleteMenuVariationValue",
     *      tags={"Menu Variation values"},
     *      summary="Delete One Menu Variation Value",
     *      description="Delete one specific menu variation value",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu variation value",
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
    public function destroy(MenuVariationValue $menuVariationValue)
    {
        foreach ($menuVariationValue->images as $image) {
            $this->deleteFile($image->slug);
        }

        $menuVariationValue->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'value' => 'required|string',
            'price' => 'required|numeric',
            'menu_variation_slug' => 'required|exists:App\Models\MenuVariation,slug',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_variation_values';
        }

        return $params;
    }

    private function getMenuVariationId($slug)
    {
        return MenuVariation::where('slug', $slug)->first()->id;
    }
}
