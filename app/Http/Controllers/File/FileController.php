<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\File;

class FileController extends Controller
{
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
            ->whereIn('extension', ['png', 'jpg'])
            ->get();
    }

    private function getSourceIdBySourceAndSlug($source, $sourceSlug)
    {
        $model = config('model.' . $source);
        return $model::where('slug', $sourceSlug)->firstOrFail()->id;
    }

    public function getFile($slug)
    {
        $file = File::where('slug', $slug)->firstOrFail();

        if ($file->extension === 'png' || $file->extension === 'jpg') {
            $path = storage_path('/app/images/large/');
        } elseif ($file->extension === 'gif') {
            $path = storage_path('/app/gifs/');
        } elseif ($file->extension === 'pdf') {
            $path = storage_path('/app/documents/');
        }

        return response()->download($path . $file->file_name);
    }

    public function getImage(Request $request, $slug)
    {
        $fileName = File::where('slug', $slug)->firstOrFail()->file_name;

        if ($request->size) {
            $imageData = config('images');
            $imageSizes = array_keys($imageData);

            if (in_array($request->size, $imageSizes)) {
                $image = storage_path($imageData[$request->size]['path']) . $fileName;
                return response()->download($image);
            }
        }

        return response()->download(storage_path('/app/images/large/') . $fileName);
    }

    public function deleteFile($slug)
    {
        $file = File::where('slug', $slug)->firstOrFail();

        if ($file->extension === 'png' || $file->extension === 'jpg') {
            Storage::disk('local')->delete('images/' . $file->file_name);
            $this->deleteImagesFromStorage($file->file_name);
        } elseif ($file->extension === 'gif') {
            Storage::disk('local')->delete('gifs/' . $file->file_name);
        } elseif ($file->extension === 'pdf') {
            Storage::disk('local')->delete('documents/' . $file->file_name);
        }

        $file->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private function deleteImagesFromStorage($fileName)
    {
        $imageSizes = array_keys(config('images'));

        foreach ($imageSizes as $size) {
            Storage::disk('local')->delete('images/' . $size . '/' . $fileName);
        }
    }
}
