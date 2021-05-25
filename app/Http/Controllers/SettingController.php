<?php

namespace App\Http\Controllers;

use App\Helpers\CacheHelper;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        return CacheHelper::getAllSettings();
    }

    public function show($groupName)
    {
        return Setting::where('group_name', $groupName)->firstOrFail();
    }

    public function updateSettings(Request $request)
    {
        $validatedData = $request->validate([
            '*.key' => 'required|exists:App\Models\Setting,key',
            '*.value' => 'required|string',
            '*.data_type' => 'required|in:string,integer,decimal',
        ]);

        Cache::forget('all_settings');

        foreach ($validatedData as $data) {
            Cache::forget($data['key']);

            $setting = Setting::where('key', $data['key'])->first();
            $setting->update([
                'value' => $data['value'],
                'data_type' => $data['data_type'],
            ]);
        }

        return response()->json('Successfully updated.', 200);
    }
}
