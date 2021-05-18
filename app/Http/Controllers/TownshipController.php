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

    /**
     * @OA\Get(
     *      path="/api/v2/admin/townships",
     *      operationId="getTownshipLists",
     *      tags={"Townships"},
     *      summary="Get list of townships",
     *      description="Returns list of townships",
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
        $sorting = CollectionHelper::getSorting('townships', 'name', $request->by, $request->order);

        return Township::with('city')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy($sorting['orderBy'], $sorting['sortBy'])
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/townships",
     *      operationId="storeTownship",
     *      tags={"Townships"},
     *      summary="Create a township",
     *      description="Returns newly created township",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created township object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Township")
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

        $validatedData = $request->validate([
            'slug' => 'required|unique:townships',
            'name' => 'required|unique:townships',
            'city_slug' => 'required|exists:App\Models\City,slug',
        ]);

        $validatedData['city_id'] = $this->getCityIdBySlug($request->city_slug);
        $township = Township::create($validatedData);

        return response()->json($township->load('city'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/townships/{slug}",
     *      operationId="showTownship",
     *      tags={"Townships"},
     *      summary="Get One Township",
     *      description="Returns a requested township",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested township",
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
    public function show(Township $township)
    {
        return response()->json($township->load('city'), 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/townships/{slug}",
     *      operationId="updateTownship",
     *      tags={"Townships"},
     *      summary="Update a township",
     *      description="Update a requested township",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a township",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New township data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Township")
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

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/townships/{slug}",
     *      operationId="deleteTownship",
     *      tags={"Townships"},
     *      summary="Delete One Township",
     *      description="Delete one specific township",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested township",
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
    public function destroy(Township $township)
    {
        $township->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getCityIdBySlug($slug)
    {
        return City::where('slug', $slug)->first()->id;
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/cities/{slug}/townships",
     *      operationId="getTownshipListsByCity",
     *      tags={"Townships"},
     *      summary="Get townships By city",
     *      description="Returns list of townships",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested city",
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
