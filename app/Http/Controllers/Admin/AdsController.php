<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdsController extends Controller
{
    use FileHelper, ResponseHelper, StringHelper;

    public function index(Request $request)
    {
        $ads = Ads::where('label', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->get();
        return $this->generateResponse($ads, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $validatedData['created_by'] = Auth::guard('users')->user()->id;

        $ads = Ads::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'ads', $ads->slug);
        }
        return response()->json($ads, 201);
    }

    public function show($slug)
    {
        $ads = Ads::where('slug', $slug)->firstOrFail();
        return response()->json($ads, 200);
    }

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
            'label' => 'nullable',
            'contact_person' => 'nullable',
            'company_name' => 'nullable',
            'phone_number' => 'nullable',
            'email' => 'nullable',
            'type' => 'required|in:banner',
            'source' => 'required|in:restaurant,shop',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:ads';
        }

        return $params;
    }
}