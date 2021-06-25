<?php

namespace App\Helpers;

trait StringHelper
{
    public static function generateUniqueSlug()
    {
        do {
            $randomString = strtoupper(substr(str_shuffle(MD5(microtime())), 0, 8));
        } while (is_numeric(substr($randomString, 0, 6)) && substr($randomString, 6, 1) == 'E');

        return $randomString;
    }

    public static function generateRandomPassword()
    {
        return substr(str_shuffle(MD5(microtime())), 0, 16);
    }

    public static function generateRandomStringLength32()
    {
        do {
            $randomString = strtoupper(strtoupper(MD5(microtime())));
        } while (is_numeric(substr($randomString, 0, 6)) && substr($randomString, 6, 1) == 'E');

        return $randomString;
    }
}
