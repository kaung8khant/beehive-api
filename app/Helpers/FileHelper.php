<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;
use App\Models\File;

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

        $model = '\App\Models\\' . str_replace(' ', '', ucwords(rtrim(str_replace('_', ' ', $source), 's')));
        $sourceId = $model::where('slug', $sourceSlug)->first()->id;

        $file->update([
            'source' => $source,
            'source_id' => $sourceId,
        ]);

        return $this->generateResponse($file, 200);
    }
}
