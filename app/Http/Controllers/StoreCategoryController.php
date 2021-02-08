<?php

namespace App\Http\Controllers;

use App\Models\StoreCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return StoreCategory::paginate(10);
    }

    public function search($filter)
    {
        return StoreCategory::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
    }

    public function getSubCategoriesByStoreCategory($slug)
    {
        $storeCategory=StoreCategory::with('sub_categories')->where('slug', $slug)->firstOrFail();
        return response()->json($storeCategory->sub_categories()->paginate(10), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug']=$this->generateUniqueSlug();

        $storeCategory=StoreCategory::create($request->validate(
            [
                'name'=>'required|unique:store_categories',
                'name_mm'=>'unique:store_categories',
                'slug'=>'required|unique:store_categories',
            ]
        ));
        return response()->json($storeCategory, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StoreCategory  $storeCategory
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(StoreCategory::where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StoreCategory  $storeCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $storeCategory=StoreCategory::where('slug', $slug)->firstOrFail();

        $storeCategory->update($request->validate([
            'name'=>'required|unique:store_categories',
            'name_mm'=>'unique:store_categories',
            Rule::unique('store_categories')->ignore($storeCategory->id),
        ]));

        return response()->json($storeCategory, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StoreCategory  $storeCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        StoreCategory::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
