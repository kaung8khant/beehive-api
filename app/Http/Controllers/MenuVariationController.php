<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\MenuVariation;
use App\Models\Menu;
use App\Models\MenuVariationValue;

class MenuVariationController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/menu-variations",
     *      operationId="getMenuVariationLists",
     *      tags={"MenuVariations"},
     *      summary="Get list of menu variation",
     *      description="Returns list of menu variation",
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
        return MenuVariation::with('menu')
            ->with('menuVariationValues')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Post(
     *      path="/api/v2/admin/menu-variations",
     *      operationId="storeMenuVariations",
     *      tags={"MenuVariations"},
     *      summary="Create a menu variation",
     *      description="Returns newly created menu variation",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created menu variation",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(
     *               @OA\Property(property="menu_slug", type="string", example="39463"),
     *               @OA\Property(property="menu_variations", type="array", @OA\Items(oneOf={
     *                  @OA\Schema(
     *                   @OA\Property(property="name", type="string", example="Name"),
     *                   @OA\Property(property="name_mm", type="string", example="အမည်"),
     *                   @OA\Property(property="menu_variation_values", type="array", @OA\Items(oneOf={
     *                    @OA\Schema(
     *                      @OA\Property(property="value", type="string", example="Name"),
     *                      @OA\Property(property="price", type="number", example=1000),
     *                      ),
     *                     })),
     *                  ),
     *                })),
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
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
            'menu_variations.*.name' => 'required|string',
            'menu_variations.*.name_mm' => 'nullable|string',
            'menu_variations.*.menu_variation_values' => 'required|array',
            'menu_variations.*.menu_variation_values.*.value' => 'required|string',
            'menu_variations.*.menu_variation_values.*.price' => 'required|numeric',
        ]);

        $menu = $this->getMenu($validatedData['menu_slug']);

        foreach ($validatedData['menu_variations'] as $menuVariation) {
            $menuVariation['slug'] = $this->generateUniqueSlug();
            $menuVariation['menu_id'] = $menu->id;

            $menuVariationId = MenuVariation::create($menuVariation)->id;

            foreach ($menuVariation['menu_variation_values'] as $menuVariationValue) {

                $menuVariationValue['slug'] = $this->generateUniqueSlug();
                $menuVariationValue['menu_variation_id'] = $menuVariationId;

                MenuVariationValue::create($menuVariationValue);
            }
        }

        $menuVariation = MenuVariation::where('menu_id', $menu->slug);

        return response()->json(['message' => 'Successfully Created.'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/menu-variations/{slug}",
     *      operationId="showMenuVariation",
     *      tags={"MenuVariations"},
     *      summary="Get One menu variation",
     *      description="Returns a requested menu variation",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu variation",
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
    public function show($slug)
    {
        $menu = MenuVariation::with('menu')->with('menuVariationValues')->where('slug', $slug)->firstOrFail();
        return response()->json($menu, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *      path="/api/v2/admin/menu-variations/{slug}",
     *      operationId="updateMenuVariation",
     *      tags={"MenuVariations"},
     *      summary="Update a menu variation",
     *      description="Update a requested menu variation",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a menu variation",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New subcategory data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/MenuVariation")
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
    public function update(Request $request, $slug)
    {
        $menuVariation = MenuVariation::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_id'] = $this->getMenu($request->menu_slug)->id;

        $menuVariation->update($validatedData);

        return response()->json($menuVariation->load('menuVariationValues'), 200);
        // return response()->json(['message' => 'Successfully Updated.'], 201);
    }


    /**
     * @OA\Get(
     *      path="/api/v2/admin/menus/{slug}/menu-variations",
     *      operationId="getVariationsByMenu",
     *      tags={"MenuVariations"},
     *      summary="Get Variations By Menu",
     *      description="Returns list of menu variations",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
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
    public function getVariationsByMenu(Request $request, $slug)
    {
        return MenuVariation::with('menuVariationValues')->whereHas('menu', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuVariation  $menuVariation
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Delete(
     *      path="/api/v2/admin/menu-variations/{slug}",
     *      operationId="deleteMenuVariation",
     *      tags={"MenuVariations"},
     *      summary="Delete One Menu Variation",
     *      description="Delete one specific menu variation",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu variation",
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
    public function destroy($slug)
    {
        MenuVariation::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = FALSE)
    {
        $params = [
            'name' => 'required|string',
            'name_mm' => 'nullable|string',
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_variations';
        }

        return $params;
    }

    private function getMenu($slug)
    {
        return Menu::where('slug', $slug)->first();
    }
}
