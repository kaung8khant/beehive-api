<?php

namespace App\Http\Controllers\Admin\v3;

use App\Helpers\CollectionHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SearchHistoryController extends Controller
{
    public function index()
    {
        $histories = DB::table('search_histories')
            ->select('keyword', DB::raw('SUM(hit_count) AS total_hit_counts'))
            ->groupBy('keyword')
            ->orderBy('total_hit_counts', 'desc')
            ->paginate(10);

        foreach ($histories as $history) {
            $history->total_hit_counts = (int) $history->total_hit_counts;
        }

        return CollectionHelper::removePaginateLinks($histories);
    }
}
