<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use  ResponseHelper;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $announcements= Content::where('type', 'announcement')
            ->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->filter . '%')
                    ->orWhere('slug', $request->filter);
            })
            ->orderBy('id', 'desc')
            ->paginate($request->size);

        return $this->generateProductResponse($announcements, 200, 'array', $announcements->lastPage());
    }
}
