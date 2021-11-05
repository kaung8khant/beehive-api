<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v2',
    'middleware' => ['cors', 'json.response'],
], function () {
    Route::post('files', 'File\UploadController@upload');
    Route::get('files/{file:slug}', 'File\FileController@getFile');
    Route::get('files/{source}/{sourceSlug}', 'File\FileController@getFilesBySource');

    Route::get('images/{file:slug}', 'File\FileController@getImage');
    Route::get('images/{source}/{sourceSlug}', 'File\FileController@getImagesBySource');

    Route::delete('files/{file:slug}', 'File\FileController@deleteFile');

    Route::get('fix-slug/{table}', 'SlugFixController@fix');

    Route::get('announcements', 'Customer\ContentController@index');

    Route::get('promotions', 'Customer\PromotionController@index');

    /* KBZ Pay Notify */
    Route::post('kbz/notify', 'Payment\KbzPayController@notify');

    Route::get('test', 'SlugFixController@test');

    // Route::post('calculate-driver', 'FirebaseController@index');
});
