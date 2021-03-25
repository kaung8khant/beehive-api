<?php

namespace App\Helpers;

use App\Helpers\ResponseHelper;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

trait FileHelper
{
    use ResponseHelper;

    protected function updateFile($slug, $source, $sourceSlug)
    {
        $file = File::where('slug', $slug)->firstOrFail();

        try {
            $validator = Validator::make(
                [
                    'source' => $source,
                    'sourceSlug' => $sourceSlug,
                ],
                [
                    'source' => 'required|string',
                    'sourceSlug' => 'required|string|exists:' . $source . ',slug',
                ],
            );

            if ($validator->fails()) {
                return $this->generateResponse($validator->errors()->first(), 422);
            }
        } catch (\Exception $e) {
            return $this->generateResponse('The selected source field is incorrect.', 422);
        }

        $model = config('model.' . $source);
        $sourceId = $model::where('slug', $sourceSlug)->first()->id;

        $file->update([
            'source' => $source,
            'source_id' => $sourceId,
        ]);

        return $this->generateResponse($file, 200);
    }

    protected function deleteFile($slug)
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
