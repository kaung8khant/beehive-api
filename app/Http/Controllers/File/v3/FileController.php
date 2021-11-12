<?php

namespace App\Http\Controllers\File\v3;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function getImage($size, $fileName)
    {
        $filePath = 'images/' . $size . '/' . $fileName;

        if (Storage::exists($filePath)) {
            return response()->file(Storage::path($filePath));
        }

        return null;
    }
}
