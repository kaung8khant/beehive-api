<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\City;

class CityController extends Controller
{
    use StringHelper;

    /**
     * @OA\Get(
     *      path="/api/v2/admin/cities",
     *      operationId="getCityLists",
     *      tags={"Cities"},
     *      summary="Get list of cities",
     *      description="Returns list of cities",
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
        return City::with('townships')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * @OA\Post(
     *      path="/api/v2/admin/cities",
     *      operationId="storeCity",
     *      tags={"Cities"},
     *      summary="Create a city",
     *      description="Returns newly created city",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created city object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/City")
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

        $city = City::create($request->validate([
            'name' => 'required|unique:cities',
            'name_mm' => 'nullable|unique:cities',
            'slug' => 'required|unique:cities',
        ]));

        return response()->json($city, 201);
    }

    /**
     * @OA\Get(
     *      path="/api/v2/admin/cities/{slug}",
     *      operationId="showCity",
     *      tags={"Cities"},
     *      summary="Get One City",
     *      description="Returns a requested city",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested city",
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
        return response()->json(City::with('townships')->where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/cities/{slug}",
     *      operationId="updateCity",
     *      tags={"Cities"},
     *      summary="Update a city",
     *      description="Update a requested city",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a city",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="New city data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/City")
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
        $city = City::where('slug', $slug)->firstOrFail();

        $city->update($request->validate([
            'name' => [
                'required',
                Rule::unique('cities')->ignore($city->id),
            ],
            'name_mm' => [
                'nullable',
                Rule::unique('cities')->ignore($city->id),
            ],
        ]));

        return response()->json($city, 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/v2/admin/cities/{slug}",
     *      operationId="showCity",
     *      tags={"Cities"},
     *      summary="Delete One City",
     *      description="Delete one specific city",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested city",
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
        City::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }
}