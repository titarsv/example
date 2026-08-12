<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routing — «Импорт страниц»
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['admin'])->group(function(){
    Route::group(['prefix' => 'page_imports'], function(){
        Route::middleware(['role:page_imports.read'])->get('/', 'PageImportController@adminIndexAction');
        Route::middleware(['role:page_imports.read'])->post('/list', 'PageImportController@adminListAction');
        Route::middleware(['role:page_imports.create'])->post('/upload', 'PageImportController@adminUploadAction');
        Route::middleware(['role:page_imports.read'])->get('/review/{id}', 'PageImportController@adminReviewAction');
        Route::middleware(['role:page_imports.write'])->post('/review/{id}/publish/{pageId}', 'PageImportController@adminPublishPageAction');
        Route::middleware(['role:page_imports.delete'])->post('/delete/{id}', 'PageImportController@adminDestroyAction');
    });
});