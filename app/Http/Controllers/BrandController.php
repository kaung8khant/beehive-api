<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    use StringHelper, FileHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
    * @OA\Get(
    *      path="/api/v2/admin/brands",
    *      operationId="getBrandLists",
    *      tags={"Brands"},
    *      summary="Get list of brands",
    *      description="Returns list of brand",
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
        return Brand::where('name', 'LIKE', '%' . $request->filter . '%')
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
     *      path="/api/v2/admin/brands",
     *      operationId="storeBrand",
     *      tags={"Brands"},
     *      summary="Create a brand",
     *      description="Returns newly created brand",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created brand object",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Brand")
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

        $brand = Brand::create($request->validate(
            [
                'name' => 'required|unique:brands',
                'slug' => 'required|unique:brands',
                'image_slug' => 'nullable|exists:App\Models\File,slug',
            ]
        ));

        $this->updateFile($request->image_slug, 'brands', $brand->slug);

        return response()->json($brand, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Get(
     *      path="/api/v2/admin/brands/{slug}",
     *      operationId="showBrands",
     *      tags={"Brands"},
     *      summary="Get One Brand",
     *      description="Returns a requested brand",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug of a requested brand",
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
        $brand = Brand::with('products')->where('slug', $slug)->firstOrFail();
        return response()->json($brand, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Put(
     *      path="/api/v2/admin/brands/{slug}",
     *      operationId="updateBrand",
     *      tags={"Brands"},
     *      summary="Update a brand",
     *      description="Update a requested brands",
     *      @OA\Parameter(
     *          name="slug",
     *          description="Slug to identify a brands",
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
     *              @OA\Schema(ref="#/components/schemas/Brand")
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
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $brand->update($request->validate([
            'name' => [
                'required',
                Rule::unique('brands')->ignore($brand->id),
            ],
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ]));

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'brands', $brand->slug);
        }

        return response()->json($brand, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    /**
    * @OA\Delete(
    *      path="/api/v2/admin/brands/{slug}",
    *      operationId="deleteBrand",
    *      tags={"Brands"},
    *      summary="Delete One Brand",
    *      description="Delete one specific brand",
    *      @OA\Parameter(
    *          name="slug",
    *          description="Slug of a requested brand",
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
        $brand =    Brand::where('slug', $slug)->firstOrFail();

        foreach ($brand->images as $image) {
            $this->deleteFile($image->slug);
        }

        $brand->delete();
        return response()->json(['message' => 'successfully deleted'], 200);
    }

    public function import(Request $request)
    {
        $validatedData=$request->validate([
            'brands' => 'nullable|array',
            'brands.*.name' => 'required|unique:brands',
        ]);

        foreach ($validatedData['brands'] as $data) {
            $data['slug'] = $this->generateUniqueSlug();
            Brand::create($data);
        }

        return response()->json(['message' => 'Success.'], 200);
    }
}
