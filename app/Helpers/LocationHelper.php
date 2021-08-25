<?php

namespace App\Helpers;

trait LocationHelper
{
    public static function orderByNearestLocation($drivers, $location)
    {
        if (isset($drivers) && count($drivers) > 0) {
            foreach ($drivers as $key => $driver) {
                $drivers[$key]['distance'] = $driver['location'];
                if (isset($driver['location']['lat']) && isset($driver['location']['lng'])) {
                    $drivers[$key]['distance'] = self::calculateDistance($location['latitude'], $location['longitude'], $driver['location']['lat'], $driver['location']['lng']);
                } else {
                    $drivers[$key]['distance'] = 6371000;
                }
            }
            array_multisort(array_column($drivers, 'distance'), SORT_ASC, $drivers);
        }

        return $drivers;
    }

    public static function calculateDistance($latFrom, $lngFrom, $latTo, $lngTo, $earthRadius = 6371000)
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
