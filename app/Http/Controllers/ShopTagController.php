<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\ShopTag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopTagController extends Controller
{
    use StringHelper;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter=$request->filter;
        return ShopTag::where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('slug', $filter)->paginate(10);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function getTagsByShop($slug)
    {
        return ShopTag::whereHas('shops', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->paginate(10);
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

        $tag=ShopTag::create($request->validate(
            [
                'name'=>'required|unique:shop_tags',
                'name_mm'=>'unique:shop_tags',
                'slug'=>'required|unique:shop_tags',
            ]
        ));
        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ShopTag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return response()->json(ShopTag::where('slug', $slug)->firstOrFail(), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShopTag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $tag=ShopTag::where('slug', $slug)->firstOrFail();

        $tag->update($request->validate([
            'name'=>'required|unique:shop_tags',
            'name_mm'=>'unique:shop_tags',
            Rule::unique('shop_tags')->ignore($tag->id),
        ]));

        return response()->json($tag, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ShopTag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        ShopTag::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
