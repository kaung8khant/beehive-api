<?php

namespace App\Http\Controllers\Excel;

use App\Http\Controllers\Controller;
use App\Imports\MenusImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MenusController extends Controller
{
    public function import(Request $request, $type)
    {
        switch ($type) {
            case 'menu':
                Excel::import(new MenusImport, $request->file('file'));
                break;
            default:
                break;
        }

        return response()->json(['message' => 'success'], 200);
    }
}
