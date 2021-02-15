<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\ShopBranch;
use Illuminate\Http\Request;

class ShopBranchController extends Controller
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
        return ShopBranch::with('shop', 'township')
        ->where('name', 'LIKE', '%' . $filter . '%')
        ->orWhere('name_mm', 'LIKE', '%' . $filter . '%')
        ->orWhere('contact_number', $filter)
        ->orWhere('slug', $filter)->paginate(10);
    }

    /**
     * Display a listing of the shop branches by one shop.
     */
    public function getBranchesByShop($slug)
    {
        return ShopBranch::whereHas('shop', function ($q) use ($slug) {
            $q->where('slug', $slug);
        })->paginate(10);
    }

    /**
     * Display a listing of the shop branches by one township.
     */
    public function getBranchesByTownship($slug)
    {
        return ShopBranch::whereHas('township', function ($q) use ($slug) {
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
        $shopBranch=ShopBranch::create($request->validate(
            [
                'slug' => 'required|unique:shop_branches',
                'name' => 'required|unique:shop_branches',
                'name_mm'=>'unique:shop_branches',
                'enable'=> 'required|boolean:shop_branches',
                'address'=> 'required',
                'contact_number' => 'required',
                'opening_time'=>'required|date_format:H:i',
                'closing_time'=>'required|date_format:H:i',
                'latitude' => 'required',
                'longitude' => 'required',
                'township_id' => 'required|exists:App\Models\Township,id',
                'shop_id' => 'required|exists:App\Models\Shop,id',
            ]
        ));
        return response()->json($shopBranch, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        response()->json(ShopBranch::with('shop', 'township')->where('slug', $slug)->firstOrFail(), 200);
    }

    /**
        * Update the specified resource in storage.
        *
        * @param  \Illuminate\Http\Request  $request
        * @param  int  $id
        * @return \Illuminate\Http\Response
        */
    public function update(Request $request, $slug)
    {
        $shopBranch = ShopBranch::where('slug', $slug)->firstOrFail();

        $shopBranch->update($request->validate([
            'name' => 'required|unique:shop_branches',
            'name_mm'=>'unique:shop_branches',
            'enable'=> 'required|boolean:shop_branches',
            'address'=> 'required',
            'contact_number' => 'required',
            'opening_time'=>'required|date_format:H:i',
            'closing_time'=>'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'township_id' => 'required|exists:App\Models\Township,id',
            'shop_id' => 'required|exists:App\Models\Shop,id',
        ]));
        return response()->json($shopBranch, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        ShopBranch::where('slug', $slug)->firstOrFail()->delete();
        return response()->json(['message'=>'successfully deleted'], 200);
    }
}
