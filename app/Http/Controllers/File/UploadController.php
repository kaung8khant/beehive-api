<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Helpers\StringHelper;
use App\Models\File;

class UploadController extends Controller
{
    use StringHelper;

    /**
     * Upload a file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $request->validate([
            'slug' => 'required|unique:files',
            'file' => 'required|file|mimes:jpg,png,gif,pdf|max:4096',
            'source' => 'required|string',
            'sourceSlug' => 'required|string|exists:' . $request->source . ',slug',
        ]);

        $file = $request->file('file');
        $fileName = $file->hashName();
        $extension = $file->getClientOriginalExtension();

        $this->storeFile($file, $fileName, $extension);

        $fileData = $this->storeData($request, $fileName, $extension);
        return response()->json($fileData, 201);
    }

    private function storeFile($file, $fileName, $extension)
    {
        if ($extension === 'png' || $extension === 'jpg') {
            $this->storeImage($file, $fileName);
        } elseif ($extension === 'gif') {
            Storage::disk('local')->put('gifs/' . $fileName, fopen($file, 'r+'));
        } elseif ($extension === 'pdf') {
            Storage::disk('local')->put('documents/' . $fileName, fopen($file, 'r+'));
        }
    }

    private function storeImage($file, $fileName)
    {
        $img = Image::make($file);
        $img->encode('png');
        $img->save(storage_path('app/images') . '/' . $fileName);

        $this->resizeImage($img, $fileName);
    }

    private function resizeImage($img, $fileName)
    {
        $images = config('images');

        foreach ($images as $image) {
            $img->heighten($image['height'], function ($constraint) {
                $constraint->upsize();
            });
            $img->save(storage_path($image['path'] . $fileName));
        }
    }

    private function storeData(Request $request, $fileName, $extension)
    {
        $model = '\App\Models\\' . str_replace(' ', '', ucwords(rtrim(str_replace('_', ' ', $request->source), 's')));
        $sourceId = $model::where('slug', $request->sourceSlug)->first()->value('id');

        return File::create([
            'slug' => $request->slug,
            'file_name' => $fileName,
            'extension' => $extension,
            'source' => $request->source,
            'source_id' => $sourceId,
        ]);
    }
}
