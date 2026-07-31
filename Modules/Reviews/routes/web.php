<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routing (moved from routes/web.php as-is)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['admin'])->group(function(){
    Route::group(['prefix' => 'reviews'], function(){
        Route::group(['prefix' => 'products'], function(){
            Route::middleware(['role:reviews.read'])->get('/', 'ReviewsController@adminIndexAction');
            Route::middleware(['role:reviews.read'])->post('/list', 'ReviewsController@adminListAction');
            Route::middleware(['role:reviews.read'])->get('/edit/{id}', 'ReviewsController@adminEditAction');
            Route::middleware(['role:reviews.write'])->post('/edit/{id}', 'ReviewsController@adminUpdateAction');
            Route::middleware(['role:reviews.write'])->post('/change_status/{id}', 'ReviewsController@adminUpdateStatusAction');
            Route::middleware(['role:reviews.write'])->post('/favorite/change_status/{id}', 'ReviewsController@adminUpdateFavoriteStatusAction');
            Route::middleware(['role:reviews.write'])->post('/update_answer/{id}', 'ReviewsController@adminUpdateAnswerAction');
            Route::middleware(['role:reviews.write'])->post('/media/{id}', 'ReviewsController@adminUpdateMediaAction');
            Route::middleware(['role:reviews.delete'])->post('/delete/{id}', 'ReviewsController@adminDestroyAction'); //softDelete
        });
        Route::group(['prefix' => 'site'], function(){
            Route::middleware(['role:reviews.read'])->get('/', 'SiteReviewsController@adminIndexAction');
            Route::middleware(['role:reviews.read'])->post('/list', 'SiteReviewsController@adminListAction');
            Route::middleware(['role:reviews.read'])->get('/edit/{id}', 'SiteReviewsController@adminEditAction');
            Route::middleware(['role:reviews.write'])->post('/edit/{id}', 'SiteReviewsController@adminUpdateAction');
            Route::middleware(['role:reviews.write'])->post('/change_status/{id}', 'SiteReviewsController@adminUpdateStatusAction');
            Route::middleware(['role:reviews.write'])->post('/favorite/change_status/{id}', 'SiteReviewsController@adminUpdateFavoriteStatusAction');
            Route::middleware(['role:reviews.write'])->post('/update_answer/{id}', 'SiteReviewsController@adminUpdateAnswerAction');
            Route::middleware(['role:reviews.write'])->post('/media/{id}', 'SiteReviewsController@adminUpdateMediaAction');
            Route::middleware(['role:reviews.delete'])->post('/delete/{id}', 'SiteReviewsController@adminDestroyAction'); //softDelete
        });
    });
});

/*
|--------------------------------------------------------------------------
| Public routing (moved from routes/web.php as-is, same locale-prefix loop
| as the core catch-all so /review/add and /shopreview/add keep working
| under a locale prefix, e.g. /ua/review/add)
|--------------------------------------------------------------------------
*/
$prefixes = config()->get('app.locales');
if(in_array(config()->get('app.locale'), $prefixes)){
    unset($prefixes[array_search(config()->get('app.locale'), $prefixes)]);
}
$prefixes[] = '';

foreach($prefixes as $prefix){
    $params = [];
    if(!empty($prefix)){
        $params = ['prefix' => '{locale}', 'where' => ['locale' => '[a-zA-Z]{2}'], 'middleware' => 'setlocale'];
    }
    Route::group($params, function(){
        Route::post('/shopreview/add', 'SiteReviewsController@addAction');
        Route::post('/review/add', 'ReviewsController@addAction');
    });
}
