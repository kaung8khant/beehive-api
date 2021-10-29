<?php

namespace App\Http\Controllers\Admin\v3;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\Request;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        return Audit::whereBetween('created_at', array($request->from, $request->to))
            ->where(function ($q) use ($request) {
                $q->where('username', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('user_slug', $request->filter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
}
