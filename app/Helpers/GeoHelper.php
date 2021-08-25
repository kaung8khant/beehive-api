<?php

namespace App\Helpers;


trait GeoHelper
{
    private static function calculateDistance($latFrom, $lngFrom, $latTo, $lngTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latFrom);
        $lonFrom = deg2rad($lngFrom);
        $latTo = deg2rad($latTo);
        $lonTo = deg2rad($lngTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        //distance in meter
        return round($angle * $earthRadius);
    }
}
