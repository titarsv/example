<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routing (moved from routes/web.php as-is)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['admin'])->group(function(){
    Route::group(['prefix' => 'articles'], function(){
        Route::middleware(['role:articles.read'])->get('/', 'BlogController@adminIndexAction');
        Route::middleware(['role:articles.read'])->post('/list', 'BlogController@adminListAction');
        Route::middleware(['role:articles.create'])->post('/create', 'BlogController@adminStoreAction');
        Route::middleware(['role:articles.read'])->get('/edit/{id}', 'BlogController@adminEditAction');
        Route::middleware(['role:articles.write'])->post('/edit/{id}', 'BlogController@adminUpdateAction');
        Route::middleware(['role:seo.write'])->post('/seo/{id}', 'BlogController@adminUpdateSeoAction');
        Route::middleware(['role:articles.write'])->post('/change_status/{id}', 'BlogController@adminUpdateStatusAction');
        Route::middleware(['role:articles.delete'])->post('/delete/{id}', 'BlogController@adminDestroyAction'); //softDelete
    });

    Route::group(['prefix' => 'content/categories'], function(){
        Route::middleware(['role:content_categories.read'])->get('/', 'ContentCategoriesController@adminIndexAction');
        Route::middleware(['role:content_categories.read'])->post('/list', 'ContentCategoriesController@adminListAction');
        Route::middleware(['role:content_categories.create'])->get('/create', 'ContentCategoriesController@adminCreateAction');
        Route::middleware(['role:content_categories.create'])->post('/create', 'ContentCategoriesController@adminStoreAction');
        Route::middleware(['role:content_categories.delete'])->post('/delete/{id}', 'ContentCategoriesController@adminDestroyAction');
        Route::middleware(['role:content_categories.read'])->get('/edit/{id}', 'ContentCategoriesController@adminEditAction');
        Route::middleware(['role:content_categories.write'])->post('/edit/{id}', 'ContentCategoriesController@adminUpdateAction');
        Route::middleware(['role:seo.write'])->post('/seo/{id}', 'ContentCategoriesController@adminUpdateSeoAction');
        Route::middleware(['role:content_categories.write'])->post('/change_status/{id}', 'ContentCategoriesController@adminUpdateStatusAction');
        Route::middleware(['role:content_categories.read'])->post('/children/{id}', 'ContentCategoriesController@adminChildrenAction');
        Route::middleware(['role:content_categories.read'])->get('/livesearch', 'ContentCategoriesController@adminLivesearchAction');
    });
});
