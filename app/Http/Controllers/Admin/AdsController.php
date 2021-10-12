<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Ads;
use App\Helpers\CollectionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdsController extends Controller
{
    use FileHelper, ResponseHelper, StringHelper;

    public function index(Request $request)
    {
        $sorting = CollectionHelper::getSorting('ads', 'id', $request->by ? $request->by : 'desc', $request->order);

        $ads = Ads::where('label', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->orderBy('id')
            ->get();

        if ($request->by) {
            $ads = $ads->orderBy($sorting['orderBy'], $sorting['sortBy'])
                ->orderBy('search_index', 'desc');
        } else {
            $ads = $ads->orderBy('search_index', 'desc')
                ->orderBy($sorting['orderBy'], $sorting['sortBy']);
        }
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
            'note' => 'nullable',
            'type' => 'required|in:banner,popup',
            'source' => 'required|in:restaurant,shop',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
            'target_type' => 'nullable|string',
            'value' => 'nullable|string',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:ads';
        }

        return $params;
    }

    public function updateSearchIndex(Request $request, Ads $ads)
    {
        $validatedData = $request->validate([
            'search_index' => 'required|numeric',
        ]);

        $ads->update($validatedData);

        return response()->json($ads, 200);
    }
}
