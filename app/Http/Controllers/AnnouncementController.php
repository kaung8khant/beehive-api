<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\StringHelper;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use FileHelper, StringHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Announcement::where('title', 'LIKE', '%' . $request->filter . '%')
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

        $validatedData = $request->validate($this->getParamsToValidate(true));

        $announcement = Announcement::create($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'announcements', $announcement->slug);
        }

        return response()->json($announcement, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $announcement = Announcement::where('slug', $slug)->firstOrFail();
        return response()->json($announcement, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {
        $announcement = Announcement::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate($this->getParamsToValidate());

        $announcement->update($validatedData);

        if ($request->image_slug) {
            $this->updateFile($request->image_slug, 'announcements', $slug);
        }

        return response()->json($announcement, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Announcement  $announcement
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $announcement = Announcement::where('slug', $slug)->firstOrFail();

        foreach ($announcement->images as $image) {
            $this->deleteFile($image->slug);
        }

        $announcement->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function getParamsToValidate($slug = false)
    {
        $params = [
            'title' => 'required|string',
            'description' => 'required|string',
            'announcement_date' => 'required|date_format:Y-m-d',
            'image_slug' => 'nullable|exists:App\Models\File,slug',
        ];

        if ($slug) {
            $params['slug'] = 'required|unique:announcements';
        }

        return $params;
    }
}
