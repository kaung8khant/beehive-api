<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use FileHelper, StringHelper;

    public function index(Request $request)
    {
        $contents = Content::where('type', $request->type)
            ->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);

        return response()->json($contents, 200);
    }

    public function store(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $content = Content::create($validatedData);

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'contents', $content->slug);
            }
        }

        return response()->json($content, 201);
    }

    public function show($slug)
    {
        $content = Content::where('slug', $slug)->firstOrFail();
        return response()->json($content, 200);
    }

    public function update(Request $request, $slug)
    {
        $content = Content::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $content->update($validatedData);

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'contents', $content->slug);
            }
        }

        return response()->json($content, 200);
    }

    public function destroy($slug)
    {
        $content = Content::where('slug', $slug)->firstOrFail();

        foreach ($content->covers as $cover) {
            $this->deleteFile($cover->slug);
        }

        $content->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'nullable|in:announcement,news,blog,branding',
            'target_type' => 'nullable|string',
            'value' => 'nullable|string',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:contents';
        }

        return $params;
    }
}
