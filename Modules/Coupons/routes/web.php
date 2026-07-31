<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routing (moved from routes/web.php as-is)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['admin'])->group(function(){
    Route::group(['prefix' => 'products/promocodes'], function(){
        Route::middleware(['role:coupons.read'])->get('/', 'CouponsController@adminIndexAction');
        Route::middleware(['role:coupons.read'])->post('/list', 'CouponsController@adminListAction');
        Route::middleware(['role:coupons.read'])->get('/create', 'CouponsController@adminCreateAction');
        Route::middleware(['role:coupons.create'])->post('/create', 'CouponsController@adminStoreAction');
        Route::middleware(['role:coupons.delete'])->post('/delete/{id}', 'CouponsController@adminDeleteAction');
        Route::middleware(['role:coupons.read'])->get('/edit/{id}', 'CouponsController@adminEditAction');
        Route::middleware(['role:coupons.write'])->post('/edit/{id}', 'CouponsController@adminUpdateAction');
        Route::middleware(['role:coupons.write'])->post('/change_status/{id}', 'CouponsController@adminUpdateStatusAction');
        Route::middleware(['role:coupons.write'])->post('/generate_code', 'CouponsController@adminGenerateCodeAction');
    });
});
