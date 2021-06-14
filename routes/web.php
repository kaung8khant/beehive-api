<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors']], function () {
    Route::get('/', function () {
        return response()->json([
            'status' => 'success',
            'build_number' => config('system.build_number'),
        ], 200);
    });
});
