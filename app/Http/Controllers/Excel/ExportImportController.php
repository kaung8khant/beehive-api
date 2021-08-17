<?php

namespace App\Http\Controllers\Excel;

use App\Exceptions\ImportException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportImportController extends Controller
{
    public function __construct()
    {
        ini_set('memory_limit', '256M');
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
                Excel::import(new $_class, $file);
                return response()->json(['message' => 'success'], 200);
            } catch (ImportException $e) {
                $this->deleteTmpFilesWhenFailed();
                $errors = json_decode($e->getMessage());
                return response()->json(['total_errors' => count($errors), 'errors' => $errors], 400);
            } catch (\Exception $e) {
                $this->deleteTmpFilesWhenFailed();
                return response()->json(['message' => $e->getMessage()], 400);
            }
        }

        return response()->json(['message' => 'failed'], 406);
    }

    public function export(Request $request, $type)
    {
        try {
            $_class = '\App\Exports\\' . config("export-import.export.{$type}");
            return Excel::download(new $_class($request->from, $request->to), $type . '-export.xlsx');
        } catch (\Exception $e) {
            $this->deleteTmpFilesWhenFailed();
            return response()->json(['message' => 'failed'], 400);
        }
    }

    public function exportWithParams(Request $request, $type, $params)
    {
        try {
            $_class = '\App\Exports\\' . config("export-import.export.{$type}");
            $fileName = $type . '-export.xlsx';

            if (in_array($type, config('export-import.name'))) {
                $fileName = $params . '-' . $type . '-export.xlsx';
            }

            return Excel::download(new $_class($params, $request->from, $request->to), $fileName);
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
