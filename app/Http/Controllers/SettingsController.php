<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Setting;

class SettingsController extends Controller
{
    use ResponseHelper;

    public function getAppVersions()
    {
        $keys = ['ios_version', 'android_version'];

        $versions = Setting::whereIn('key', $keys)->pluck('value', 'key')->map(function ($version) {
            $versionSplit = explode('.', $version);

            return [
                'major' => (int) $versionSplit[0],
                'minor' => (int) $versionSplit[1],
                'patch' => (int) $versionSplit[2],
            ];
        });

        return $this->generateResponse($versions, 200);
    }
}
