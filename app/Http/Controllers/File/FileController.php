<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:users')->only('deleteFile') || $this->middleware('auth:vendors')->only('deleteFile');
    }

    public function getFilesBySource($source, $sourceSlug)
    {
        $sourceId = $this->getSourceIdBySourceAndSlug($source, $sourceSlug);

        return File::where('source', $source)
            ->where('source_id', $sourceId)
            ->get();
    }

    public function getImagesBySource($source, $sourceSlug)
    {
        $sourceId = $this->getSourceIdBySourceAndSlug($source, $sourceSlug);

        return File::where('source', $source)
            ->where('source_id', $sourceId)
            ->whereIn('extension', ['png', 'jpg', 'jpeg'])
            ->get();
    }

    private function getSourceIdBySourceAndSlug($source, $sourceSlug)
    {
        $model = config('model.' . $source);
        return $model::where('slug', $sourceSlug)->firstOrFail()->id;
    }

    public function getFile(File $file)
    {
        $path = 'images/large/';

        if (Storage::exists($path . $file->file_name)) {
            return Storage::download($path . $file->file_name);
        }

        // if ($file->extension === 'png' || $file->extension === 'jpg' || $file->extension === 'jpeg') {
        //     $path = 'images/large/';
        // } elseif ($file->extension === 'gif') {
        //     $path = 'gifs/';
        // } elseif ($file->extension === 'pdf') {
        //     $path = 'documents/';
        // }
    }

    public function getImage(Request $request, File $file)
    {
        $imageData = config('images');
        $imageSizes = array_keys($imageData);

        if (!$request->size) {
            $request->size = 'large';
        }

        if (in_array($request->size, $imageSizes) && Storage::exists($imageData[$request->size]['path'] . $file->file_name)) {
            return Storage::download($imageData[$request->size]['path'] . $file->file_name);
        }

        return null;
    }

    public function deleteFile(File $file)
    {
        $this->deleteImagesFromStorage($file->file_name);
        $file->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);

        // if ($file->extension === 'png' || $file->extension === 'jpg' || $file->extension === 'jpeg') {
        //     $this->deleteImagesFromStorage($file->file_name);
        // } elseif ($file->extension === 'gif') {
        //     Storage::delete('gifs/' . $file->file_name);
        // } elseif ($file->extension === 'pdf') {
        //     Storage::delete('documents/' . $file->file_name);
        // }
    }

    private function deleteImagesFromStorage($fileName)
    {
        $imageSizes = array_keys(config('images'));

        foreach ($imageSizes as $size) {
            Storage::delete('images/' . $size . '/' . $fileName);
        }
    }
}
