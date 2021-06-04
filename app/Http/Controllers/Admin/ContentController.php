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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $contents= Content::where('type', $request->type)
            ->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->paginate(10);

        return response()->json($contents, 200);
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

        $content = Content::create($validatedData);

        if ($request->cover_slugs) {
            foreach ($request->cover_slugs as $coverSlug) {
                $this->updateFile($coverSlug, 'contents', $content->slug);
            }
        }

        return response()->json($content, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Content  $content
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $content = Content::where('slug', $slug)->firstOrFail();
        return response()->json($content, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Content  $content
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Content  $content
     * @return \Illuminate\Http\Response
     */
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
            'type' => 'nullable|in:announcement,news,blog',
            'cover_slugs' => 'nullable|array',
            'cover_slugs.*' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:contents';
        }

        return $params;
    }
}
