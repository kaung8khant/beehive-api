<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Models\MenuTopping;
use App\Models\MenuToppingValue;
use App\Models\Menu;

class MenuToppingController extends Controller
{
    use StringHelper, FileHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *      path="/api/v2/admin/menu-toppings",
     *      operationId="getMenuToppingLists",
     *      tags={"MenuToppings"},
     *      summary="Get list of menu toppings",
     *      description="Returns list of menu toppings",
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
        return MenuTopping::with('menu')
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
     *      path="/api/v2/admin/menu-toppings",
     *      operationId="storeMenuTopping",
     *      tags={"MenuToppings"},
     *      summary="Create Menu Toppings",
     *      description="Returns newly created Menu Topping list",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Remove ShopCategories in a shop",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(
     *               @OA\Property(property="menu_slug", type="string", example="D16AAF"),
     *               @OA\Property(property="menu_toppings", type="array", @OA\Items(oneOf={
     *                @OA\Schema(
     *                   @OA\Property(property="name", type="string", example="Name"),
     *                   @OA\Property(property="price", type="number", example=1000),
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
            'menu_toppings.*.name' => 'required|unique:menu_toppings',
            'menu_toppings.*.price' => 'required|numeric',
            'menu_toppings.*.image_slug' => 'nullable|exists:App\Models\File,slug',
         ]);

        $menuId = $this->getMenuId($validatedData['menu_slug']);

        foreach ($validatedData['menu_toppings'] as $menuTopping) {
            $menuTopping['slug'] = $this->generateUniqueSlug();
            $menuTopping['menu_id'] = $menuId;
            MenuTopping::create($menuTopping)->id;
            if (!empty($menuTopping->image_slug)) {
                $this->updateFile($menuTopping['image_slug'], 'menu_toppings', $menuTopping['slug']);
            }
        }


        return response()->json(['message' => 'Successfully Created.'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Get(
     *      path="/api/v2/admin/menu-toppings/{slug}",
     *      operationId="showMenuTopping",
     *      tags={"MenuToppings"},
     *      summary="Get One Menu Topping",
     *      description="Returns a requested menu topping",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu topping",
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
        $menuTopping = MenuTopping::with('menu')->where('slug', $slug)->firstOrFail();
        return response()->json($menuTopping, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Put(
     *      path="/api/v2/admin/menu-toppings/{slug}",
     *      operationId="updateMenuTopping",
     *      tags={"MenuToppings"},
     *      summary="Update a Menu Topping",
     *      description="Update a requested Menu Topping",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a Menu Topping",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New menu topping data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/MenuTopping")
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
        $menuTopping = MenuTopping::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());
        $validatedData['menu_id'] = $this->getMenuId($request->menu_slug);

        $menuTopping->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'menu_toppings', $menuTopping->slug);
        }

        return response()->json($menuTopping->load('menu'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuTopping  $menuTopping
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/menu-toppings/{slug}",
     *      operationId="deleteMenuTopping",
     *      tags={"MenuToppings"},
     *      summary="Delete One Menu Topping",
     *      description="Delete one specific menu topping",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested menu topping",
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
        $menuTopping = MenuTopping::where('slug', $slug)->firstOrFail();

        foreach ($menuTopping->images as $image) {
            $this->deleteFile($image->slug);
        }

        $menuTopping->delete();

        return response()->json(['message' => 'Successfully deleted.'], 200);
    }


    /**
     * @OA\Get(
     *      path="/api/v2/admin/menus/{slug}/menu-toppings",
     *      operationId="getToppingsByMenu",
     *      tags={"MenuToppings"},
     *      summary="Get Toppings By Menu",
     *      description="Returns list of menu toppings",
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
    public function getToppingsByMenu(Request $request, $slug)
    {
        return MenuTopping::whereHas('menu', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }


    private function getParamsToValidate($slug = false)
    {
        $params = [
            'name' => 'required|string',
            'price' => 'required|numeric',
            'menu_slug' => 'required|exists:App\Models\Menu,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menu_toppings';
        }

        return $params;
    }

    private function getMenuId($slug)
    {
        return Menu::where('slug', $slug)->first()->id;
    }
}
