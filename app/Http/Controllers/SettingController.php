<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Setting::all();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function show($key)
    {
        return Setting::where('key', $key)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSetting(Request $request)
    {
        $validatedData = $request->validate([
            '*.key' => 'required|exists:App\Models\Setting,key',
            '*.value' => 'required|string',
            '*.data_type' => 'required|in:string,integer,decimal',
        ]);

        foreach ($validatedData as $data) {
            Setting::where('key', $data['key'])->update([
                'key' => $data['key'],
                'value' => $data['value'],
                'data_type' => $data['data_type'],
            ]);
        }

        return response()->json('Successfully updated.', 200);
    }
}
