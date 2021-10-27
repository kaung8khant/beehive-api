<?php

namespace App\Http\Controllers\Customer\v3;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchHistoryController extends Controller
{
    use ResponseHelper;

    public function index(Request $request)
    {
        $histories = DB::table('search_histories')
            ->select('keyword', 'created_at')
            ->where('device_id', $request->device_id)
            ->orderBy('id', 'desc')
            ->limit($request->size ? $request->size : 10)
            ->get();

        return $this->generateResponse($histories, 200);
    }
}
