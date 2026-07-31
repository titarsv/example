<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routing (moved from routes/web.php as-is)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['admin'])->group(function(){
    Route::group(['prefix' => 'metadata'], function(){
        Route::get('/', 'MetadataController@index');
        Route::post('/generate', 'MetadataController@generate');
        Route::get('/progress', 'MetadataController@getProgress');
    });

    Route::group(['prefix' => 'seo-content'], function(){
        Route::get('/', 'SeoContentController@index');
        Route::post('/generate', 'SeoContentController@generate');
        Route::get('/progress', 'SeoContentController@getProgress');
    });

    Route::group(['prefix' => 'gemini-translate'], function(){
        Route::get('/', 'GeminiTranslateController@index');
        Route::post('/generate', 'GeminiTranslateController@generate');
        Route::post('/generate-type', 'GeminiTranslateController@generateType');
        Route::get('/progress', 'GeminiTranslateController@getProgress');
    });
});
