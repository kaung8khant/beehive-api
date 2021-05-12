<?php

namespace App\Http\Controllers;

class SlugFixController
{
    public function fix($table)
    {
        $model = '\App\Models\\' . $table;
        $data = $model::all();

        foreach ($data as $key) {
            if (is_numeric(substr($key->slug, 0, 6)) && substr($key->slug, 6, 1) == 'E') {
                do {
                    $randomString = strtoupper(substr(str_shuffle(MD5(microtime())), 0, 8));
                } while (is_numeric(substr($randomString, 0, 6)) && substr($randomString, 6, 1) == 'E');

                $key->slug = $randomString;
                $key->save();
            }
        }

        return ['status' => 'success'];
    }
}
