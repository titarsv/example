<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Telegram webhook (moved from routes/web.php as-is, same locale-prefix
| loop as the core catch-all - Telegram itself always calls the bare
| /telegram_api path, but this preserves the {locale}/telegram_api
| variants that existed before the move)
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
        Route::post('/telegram_api', 'TelegramController@index');
    });
}
