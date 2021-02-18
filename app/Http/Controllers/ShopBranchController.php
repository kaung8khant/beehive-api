<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;
use App\Models\ShopBranch;
use App\Models\Shop;
use App\Models\Township;

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
        return ShopBranch::with('shop', 'township')
            ->where('name', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('name_mm', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('contact_number', $request->filter)
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate([
            'slug' => 'required|unique:shop_branches',
            'name' => 'required|unique:shop_branches',
            'name_mm' => 'nullable|unique:shop_branches',
            'address' => 'required',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'township_slug' => 'required|exists:App\Models\Township,slug',
            'is_enable' => 'nullable|boolean',
        ]);

        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $shopBranch = ShopBranch::create($validatedData);
        return response()->json($shopBranch->load('shop', 'township'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $shopBranch = ShopBranch::with('shop', 'township')->where('slug', $slug)->firstOrFail();
        return response()->json($shopBranch, 200);
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

        $validatedData = $request->validate([
            'name' => [
                'required',
                Rule::unique('shop_branches')->ignore($shopBranch->id),
            ],
            'name_mm' => [
                'nullable',
                Rule::unique('shop_branches')->ignore($shopBranch->id),
            ],
            'address' => 'required',
            'contact_number' => 'required',
            'opening_time' => 'required|date_format:H:i',
            'closing_time' => 'required|date_format:H:i',
            'latitude' => 'required',
            'longitude' => 'required',
            'shop_slug' => 'required|exists:App\Models\Shop,slug',
            'township_slug' => 'required|exists:App\Models\Township,slug',
            'is_enable' => 'nullable|boolean',
        ]);

        $validatedData['shop_id'] = $this->getShopId($request->shop_slug);
        $validatedData['township_id'] = $this->getTownshipId($request->township_slug);

        $shopBranch->update($validatedData);
        return response()->json($shopBranch->load('shop', 'township'), 200);
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
        return response()->json(['message' => 'Successfully deleted'], 200);
    }

    private function getShopId($slug)
    {
        return Shop::where('slug', $slug)->first()->id;
    }

    private function getTownshipId($slug)
    {
        return Township::where('slug', $slug)->first()->id;
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
}
