<?php

namespace App\Http\Controllers\Excel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ExportImportController extends Controller
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 300);
    }

    public function import(Request $request, $type)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv,txt',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            try {
                $_class = '\App\Imports\\' . config("export-import.import.{$type}");
                $import = new $_class();
                $import->import($file);
                // Excel::import(new $_class, $file);
                if (count($import->failures()) > 0) {
                    return response()->json($import->failures(), 400);
                }
                return response()->json(['message' => 'success'], 200);
            } catch (ValidationException $e) {
                $this->deleteTmpFilesWhenFailed();
                return response()->json($e->failures(), 400);
            } catch (\Exception $e) {
                $this->deleteTmpFilesWhenFailed();
                return response()->json(['message' => $e->getMessage()], 400);
            }
        }

        return response()->json(['message' => 'failed'], 406);
    }

    public function export($type)
    {
        try {
            $_class = '\App\Exports\\' . config("export-import.export.{$type}");
            return Excel::download(new $_class, $type . '-export.xlsx');
        } catch (\Exception $e) {
            $this->deleteTmpFilesWhenFailed();
            return response()->json(['message' => 'failed'], 400);
        }
    }

    public function exportWithParams($type, $params)
    {
        try {
            $_class = '\App\Exports\\' . config("export-import.export.{$type}");
            return Excel::download(new $_class($params), $type . '-export.xlsx');
        } catch (\Exception $e) {
            $this->deleteTmpFilesWhenFailed();
            return response()->json(['message' => 'failed'], 400);
        }
    }

    private function deleteTmpFilesWhenFailed()
    {
        $files = Storage::disk('excel')->files();

        foreach ($files as $file) {
            Storage::disk('excel')->delete($file);
        }
    }
}
