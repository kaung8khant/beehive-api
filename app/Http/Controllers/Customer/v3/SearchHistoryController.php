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
            ->select('keyword')
            ->where('device_id', $request->device_id);

        if ($request->type) {
            $histories = $histories->where('type', $request->type);
        }

        $histories = $histories->orderBy('updated_at', 'desc')
            ->limit($request->size ? $request->size : 10)
            ->get()
            ->unique()
            ->values();

        return $this->generateResponse($histories, 200);
    }

    public function clearHistory(Request $request)
    {
        DB::table('search_histories')
            ->where('device_id', $request->device_id)
            ->where('type', $request->type)
            ->delete();

        return $this->generateResponse('success', 200, true);
    }
}
