<?php

namespace App\Helpers;

use App\Events\DataChanged;
use App\Helpers\ResponseHelper;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

trait FileHelper
{
    use ResponseHelper;

    public static function updateFile($slug, $source, $sourceSlug, $user = null, $url = '/')
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
                return ResponseHelper::generateResponse($validator->errors()->first(), 422);
            }
        } catch (\Exception $e) {
            return ResponseHelper::generateResponse('The selected source field is incorrect.', 422);
        }

        $model = config('model.' . $source);
        $sourceId = $model::where('slug', $sourceSlug)->value('id');

        if ($user && ($file->source !== $source || $file->source_id !== $sourceId)) {
            DataChanged::dispatch($user, 'upload', $source, $sourceSlug, $url, 'success', $file);
        }

        $file->update([
            'source' => $source,
            'source_id' => $sourceId,
        ]);

        return ResponseHelper::generateResponse($file, 200);
    }

    public static function deleteFile($slug)
    {
        $file = File::where('slug', $slug)->firstOrFail();

        if ($file->extension === 'png' || $file->extension === 'jpg' || $file->extension === 'jpeg') {
            self::deleteImagesFromStorage($file->file_name);
        } elseif ($file->extension === 'gif') {
            Storage::delete('gifs/' . $file->file_name);
        } elseif ($file->extension === 'pdf') {
            Storage::delete('documents/' . $file->file_name);
        }

        $file->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    private static function deleteImagesFromStorage($fileName)
    {
        $imageSizes = array_keys(config('images'));

        foreach ($imageSizes as $size) {
            Storage::delete('images/' . $size . '/' . $fileName);
        }
    }
}
