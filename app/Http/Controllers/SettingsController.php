<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Setting;

class SettingsController extends Controller
{
    use ResponseHelper;

    public function getAppVersions()
    {
        $keys = ['ios_version', 'android_version', 'admin_panel_version'];

        $versions = Setting::whereIn('key', $keys)->pluck('value', 'key')->map(function ($version) {
            $versionSplit = explode('.', $version);

            return [
                'major' => isset($versionSplit[0]) ? (int) $versionSplit[0] : 0,
                'minor' => isset($versionSplit[1]) ? (int) $versionSplit[1] : 0,
                'patch' => isset($versionSplit[2]) ? (int) $versionSplit[2] : 0,
                'build' => isset($versionSplit[3]) ? (int) $versionSplit[3] : 0,
            ];
        });

        return $this->generateResponse($versions, 200);
    }
}
