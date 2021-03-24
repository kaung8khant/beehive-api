<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\Township;
use App\Models\City;

class TownshipController extends Controller
{
    use StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
        return Township::with('city')
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
            'city_slug' => 'required|exists:App\Models\City,slug'
        ]);

        $validatedData['city_id'] = $this->getCityIdBySlug($request->city_slug);

        $township = Township::create($validatedData);
        return response()->json($township, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
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
    public function show($slug)
    {
        $township = Township::with('city')->where('slug', $slug)->firstOrFail();
        return response()->json($township, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
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
    public function update(Request $request, $slug)
    {
        $township = Township::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('townships')->ignore($township->id),
            ],
            'city_slug' => 'required|exists:App\Models\City,slug',
        ]);

        $validatedData['city_id'] = $this->getCityIdBySlug($request->city_slug);
        $township->update($validatedData);

        return response()->json($township, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $slug
     * @return \Illuminate\Http\Response
     */
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
    public function destroy($slug)
    {
        Township::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getCityIdBySlug($slug)
    {
        return City::where('slug', $slug)->first()->id;
    }

    /**
     * Display a listing of the townships by one city.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
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
    public function getTownshipsByCity(Request $request, $slug)
    {
        return Township::whereHas('city', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->where(function ($q) use ($request) {
            $q->where('name', 'LIKE', '%' . $request->filter . '%')
                ->orWhere('slug', $request->filter);
        })->paginate(10);
    }
}
