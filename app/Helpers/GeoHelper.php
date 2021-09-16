<?php

namespace App\Helpers;

trait GeoHelper
{
    public static function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $theta = deg2rad($lng2) - deg2rad($lng1);
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos($theta);
        return 6371 * acos($dist);
    }

    public static function calculateDeliveryTime($distance)
    {
        if ($distance <= 1) {
            $time = '30m';
        } elseif ($distance <= 3) {
            $time = '45m';
        } elseif ($distance <= 5) {
            $time = '1hr';
        } elseif ($distance <= 25) {
            $time = '2hr';
        } else {
            $time = '48hr';
        }

        return $time;
    }

    public static function calculateDeliveryFee($distance)
    {
        $fee = 1500;

        if ($distance > 3) {
            $extraKilo = intval($distance) - 3;

            if (is_numeric($distance) && floor($distance) != $distance) {
                $extraKilo = $extraKilo + 1;
            }

            $extraFee = $extraKilo * 300;
            $fee = $fee + $extraFee;
        }

        return $fee;
    }
}
