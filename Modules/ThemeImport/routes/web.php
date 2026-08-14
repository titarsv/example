<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routing — «Импорт темы» (динамические страницы + генерация темы)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['admin'])->group(function(){
    Route::group(['prefix' => 'theme_imports'], function(){
        Route::middleware(['role:theme_imports.read'])->get('/', 'ThemeImportController@adminIndexAction');
        Route::middleware(['role:theme_imports.read'])->post('/list', 'ThemeImportController@adminListAction');
        Route::middleware(['role:theme_imports.create'])->post('/upload', 'ThemeImportController@adminUploadAction');
        Route::middleware(['role:theme_imports.read'])->get('/review/{id}', 'ThemeImportController@adminReviewAction');
        Route::middleware(['role:theme_imports.read'])->get('/preview/{id}/{type}', 'ThemeImportController@adminPreviewAction');
        Route::middleware(['role:theme_imports.delete'])->post('/delete/{id}', 'ThemeImportController@adminDestroyAction');
    });
});