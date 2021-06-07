<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Promocode;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $promotions= Promotion::with('promocode')->where('title', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);

        return response()->json($promotions, 200);
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

        $validatedData = $request->validate($this->getParamsToValidate(true));
        $validatedData['promocode_id'] = $this->getPromocodeIdBySlug($request->promocode_slug);
        $promotion = Promotion::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'promotions', $promotion->slug);
        }

        return response()->json($promotion->refresh()->load('promocode'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Promotion  $promotion
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $promotion = Promotion::where('slug', $slug)->firstOrFail();
        return response()->json($promotion, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Promotion  $promotion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $promotion = Promotion::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $validatedData['promocode_id'] = $this->getPromocodeIdBySlug($request->promocode_slug);

        $promotion->update($validatedData);
        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'promotions', $slug);
        }

        return response()->json($promotion->refresh()->load('promocode'), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Promotion  $promotion
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $promotion = Promotion::where('slug', $slug)->firstOrFail();

        foreach ($promotion->covers as $cover) {
            $this->deleteFile($cover->slug);
        }

        $promotion->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'title' => 'required|string',
            'target_type' => 'nullable|string',
            'target_slug' => 'nullable|string',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'promocode_slug' => 'nullable|exists:App\Models\Promocode,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:promotions';
        }

        return $params;
    }


    private function getPromocodeIdBySlug($slug)
    {
        return Promocode::where('slug', $slug)->first()->id;
    }
}
