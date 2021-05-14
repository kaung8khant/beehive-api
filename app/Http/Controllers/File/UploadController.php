<?php

namespace App\Http\Controllers\File;

use App\Helpers\StringHelper;
use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadController extends Controller
{
    use StringHelper;

    public function __construct()
    {
        $this->middleware('auth:users') || $this->middleware('auth:vendors');
    }

    /**
     * @OA\Post(
     *      path="/api/v2/files",
     *      operationId="fileUpload",
     *      tags={"Files"},
     *      summary="Upload a file",
     *      description="Returns newly uploaded file",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Created city object",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(@OA\Property(property="file", type="file"),)
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function upload(Request $request)
    {
        $request['slug'] = $this->generateUniqueSlug();

        $request->validate([
            'slug' => 'required|unique:files',
            'file' => 'required|file|mimes:jpg,jpeg,png|max:4096',
            'source' => 'nullable|string',
            'sourceSlug' => 'nullable|string|exists:' . $request->source . ',slug',
            'type' => 'nullable|string|in:image,cover',
        ]);

        $file = $request->file('file');
        $fileName = $file->hashName();
        $extension = strtolower($file->getClientOriginalExtension());

        $this->storeFile($file, $fileName, $extension);

        $fileData = $this->storeData($request, $fileName, $extension);
        return response()->json($fileData, 201);
    }

    private function storeFile($file, $fileName, $extension)
    {
        $this->storeImage($file, $fileName);

        // if ($extension === 'png' || $extension === 'jpg' || $extension === 'jpeg') {
        //     $this->storeImage($file, $fileName);
        // } elseif ($extension === 'gif') {
        //     Storage::put('gifs/' . $fileName, fopen($file, 'r+'));
        // } elseif ($extension === 'pdf') {
        //     Storage::put('documents/' . $fileName, fopen($file, 'r+'));
        // }
    }

    private function storeImage($file, $fileName)
    {
        $img = Image::make($file);
        $img->encode('png');
        $this->resizeImage($img, $fileName);
    }

    private function resizeImage($img, $fileName)
    {
        $imgOptions = config('images');

        foreach ($imgOptions as $option) {
            $image = $img->heighten($option['height'], function ($constraint) {
                $constraint->upsize();
            })->stream();

            Storage::put($option['path'] . $fileName, $image->__toString());
        }
    }

    private function storeData(Request $request, $fileName, $extension)
    {
        return File::create([
            'slug' => $request->slug,
            'file_name' => $fileName,
            'extension' => $extension,
            'type' => $request->type ? $request->type : 'image',
        ]);
    }
}
