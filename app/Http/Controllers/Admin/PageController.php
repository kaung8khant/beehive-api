<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        return Page::all();
    }

    public function show(Page $page)
    {
        return $page;
    }

    public function update(Request $request, Page $page)
    {
        $validatedData = $request->validate(
            [
                'name' => [
                    'required',
                    Rule::unique('pages')->ignore($page->id),
                ],
                'content' => 'nullable',
            ]
        );

        $page->update($validatedData);
        return response()->json($page, 200);
    }
}
