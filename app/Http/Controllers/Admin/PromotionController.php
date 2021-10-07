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

    public function index(Request $request)
    {
        $promotions = Promotion::with('promocode')->where('title', 'LIKE', '%' . $request->filter . '%')
            ->orWhere('slug', $request->filter)
            ->paginate(10);

        return response()->json($promotions, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));
        if ($request->promocode_slug) {
            $validatedData['promocode_id'] = $this->getPromocodeIdBySlug($request->promocode_slug);
        }
        $promotion = Promotion::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'promotions', $promotion->slug);
        }

        return response()->json($promotion->refresh()->load('promocode'), 201);
    }

    public function show($slug)
    {
        $promotion = Promotion::where('slug', $slug)->firstOrFail();
        return response()->json($promotion, 200);
    }

    public function update(Request $request, $slug)
    {
        $promotion = Promotion::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        if ($request->promocode_slug) {
            $validatedData['promocode_id'] = $this->getPromocodeIdBySlug($request->promocode_slug);
        } else {
            $validatedData['promocode_id'] = null;
        }

        $promotion->update($validatedData);
        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'promotions', $slug);
        }

        return response()->json($promotion->refresh()->load('promocode'), 200);
    }

    public function destroy($slug)
    {
        $promotion = Promotion::where('slug', $slug)->firstOrFail();

        foreach ($promotion->images as $image) {
            $this->deleteFile($image->slug);
        }

        $promotion->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'title' => 'required|string',
            'target_type' => 'nullable|string',
            'value' => 'nullable|string',
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
