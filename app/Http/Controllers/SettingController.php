<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return Setting::where('key',$key)->firstOrFail();
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function update_setting(Request $request)
    {

        // foreach($request as $data){
        //     $setting = Setting::where('key',$data->key);
        //     $setting->value = $data->value;
        //     $setting->data_type = $data->data_type;
        //     $setting->save();
        // }

        foreach ($request as $data) {
           Setting::where('key',$data->key)->update([
            'key' => $data->key,
            'value' => $data->value,
            'data_type' => $data->data_type
           ]);
        }


        return response()->json('Updated', 200);
    }

}