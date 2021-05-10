<?php

namespace App\Http\Controllers\Excel;

use App\Http\Controllers\Controller;
use App\Imports\MenusImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function importMenus(Request $request, $type)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv,txt',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            try {
                switch ($type) {
                    case 'menu':
                        Excel::import(new MenusImport, $file);
                        break;
                    default:
                        break;
                }

                return response()->json(['message' => 'success'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => 'failed'], 400);
            }
        }

        return response()->json(['message' => 'failed'], 406);
    }
}
