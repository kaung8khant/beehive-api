<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Models\Ads;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    use FileHelper, ResponseHelper, StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Ads::where('label', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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

        $ads = Ads::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'ads', $ads->slug);
        }
        return response()->json($ads, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ads  $ads
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $ads = Ads::where('slug', $slug)->firstOrFail();
        return response()->json($ads, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Ads  $ads
     * @return \Illuminate\Http\Response
     */
    public function edit(Ads $ads)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ads  $ads
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $ads = Ads::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());


        $ads->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'ads', $slug);
        }

        return response()->json($ads, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ads  $ads
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $ads = Ads::where('slug', $slug)->firstOrFail();

        foreach ($ads->images as $image) {
            $this->deleteFile($image->slug);
        }

        $ads->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'label' => 'required',
            'contact_person' => 'required',
            'company_name' => 'required',
            'phone_number' => 'required',
            'email' => 'nullable',
            'type' => 'required|in:banner',
            'source' => 'required|in:restaurant,shop',
            'created_by' => 'required',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:menus';
        }

        return $params;
    }
}
