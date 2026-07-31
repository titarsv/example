<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/xmlsitemap', function () {
    Artisan::call('xmlsitemap');
});
Route::get('/update_google_reviews', function () {
    Artisan::call('update_google_reviews');
});

// locale Route
Route::get('lang/{locale}', 'LanguageController@swap');

/**
 * Admin routing
 */
Route::prefix('admin')->middleware(['admin'])->group(callback: function(){
    Route::get('/', function () {
        return redirect('/admin/orders');
    });

//    Route::get('/', 'AdminController@dashAction');
    Route::post('/ajax/{method}', 'AjaxController@back');

    Route::post('/cacheflush', function() {
        cache()->flush();
        return response()->json(['result' => 'success']);
    });

    Route::get('/loadimages', 'ImagesController@loadImages');
    Route::post('/upload', 'ImagesController@uploadImages');

    Route::group(['prefix' => 'images'], function(){
        Route::post('/start_updating', 'ImagesController@startUpdatingImages');
        Route::post('/update_sizes', 'ImagesController@updateImageSize');
        Route::post('/remove_images', 'ImagesController@removeImages');
    });

    // Роуты metadata/seo-content/gemini-translate перенесены в Modules/Ai/routes/web.php

//    Route::group(['prefix' => 'index'], function(){
//        Route::get('/products', 'IndexController@products');
//        Route::get('/filters', 'IndexController@filters');
//    });

    Route::group(['prefix' => 'menu'], function(){
        Route::get('/', 'MenuController@adminIndexAction');
        Route::post('/create', 'MenuController@adminStoreAction');
        Route::get('/edit/{id}', 'MenuController@adminShowAction');
        Route::post('/edit/{id}', 'MenuController@adminUpdateAction');
        Route::post('/delete/{id}', 'MenuController@adminDestroyAction');
        Route::post('/updatestatus/{id}', 'MenuController@adminUpdateStatusAction');
    });

//    Route::group(['prefix' => 'sales'], function(){
//        Route::middleware(['role:sales.list'])->get('/', 'SalesController@adminIndexAction');
//        Route::middleware(['role:sales.create'])->get('/create', 'SalesController@adminCreateAction');
//        Route::middleware(['role:sales.create'])->post('/create', 'SalesController@adminStoreAction');
//        Route::middleware(['role:sales.delete'])->get('/delete/{id}', 'SalesController@adminDestroyAction');
//        Route::middleware(['role:sales.view'])->get('/edit/{id}', 'SalesController@adminEditAction');
//        Route::middleware(['role:sales.write'])->post('/edit/{id}', 'SalesController@adminUpdateAction');
//        Route::middleware(['role:sales.create,sales.write'])->post('/add_product', 'AjaxController@adminAddSaleProduct');
//        Route::middleware(['role:sales.create,sales.write'])->post('/remove_product', 'AjaxController@adminRemoveSaleProduct');
//    });

    Route::group(['prefix' => 'products'], function(){
        Route::middleware(['role:products.read'])->any('/', 'ProductsController@adminIndexAction');
        Route::middleware(['role:categories.read'])->post('/list', 'ProductsController@adminListAction');
        Route::middleware(['role:products.create'])->get('/create', 'ProductsController@adminCreateAction');
        Route::middleware(['role:products.create'])->post('/create', 'ProductsController@adminStoreAction');
        Route::middleware(['role:products.delete'])->post('/delete/{id}', 'ProductsController@adminDeleteAction');
        Route::middleware(['role:products.read'])->get('/edit/{id}', 'ProductsController@adminEditAction');
        Route::middleware(['role:products.write'])->post('/edit/{id}', 'ProductsController@adminUpdateAction');
        Route::middleware(['role:products.write'])->post('/related/{id}', 'ProductsController@adminUpdateRelatedAction');
        Route::middleware(['role:seo.write'])->post('/seo/{id}', 'ProductsController@adminUpdateSeoAction');
        Route::middleware(['role:products.write'])->post('/change_status/{id}', 'ProductsController@adminUpdateStatusAction');
        Route::middleware(['role:products.write'])->post('/sync_attributes/{id}', 'ProductsController@adminSyncAttributesAction');
        Route::middleware(['role:products.write'])->post('/update_price/{id}', 'ProductsController@adminUpdatePriceAction');
        Route::middleware(['role:products.write'])->post('/update_stock/{id}', 'ProductsController@adminUpdateStockAction');
        Route::middleware(['role:products.create'])->get('/duplicate/{id}', 'ProductsController@adminDuplicateAction');
        Route::middleware(['role:products.create'])->get('/getattributevalues', 'ProductsController@getAttributes');
        Route::middleware(['role:products.create'])->post('/getattributevalues', 'ProductsController@getAttributeValues');
        Route::middleware(['role:products.write'])->post('/updatestok/{id}', 'ProductsController@adminUpdateVisibilityAction');
        Route::middleware(['role:products.write,products.delete'])->get('/get_filtered_ids', 'ProductsController@adminGetFilteredIdsAction');
        Route::middleware(['role:products.write'])->post('/mass_action/{id}', 'ProductsController@adminMassAction');
        Route::middleware(['role:products.write'])->post('/livesearch', 'AjaxController@adminLiveSearchAction');
        Route::middleware(['role:products.write'])->post('/video_reviews/{id}', 'ProductsController@adminVideoReviewsAction');
        Route::group(['prefix' => 'variations'], function(){
            Route::middleware(['role:categories.read'])->post('/{id}', 'VariationsController@adminUpdateAction');
        });
        Route::group(['prefix' => 'categories'], function(){
            Route::middleware(['role:categories.read'])->get('/', 'CategoriesController@adminIndexAction');
            Route::middleware(['role:categories.read'])->post('/list', 'CategoriesController@adminListAction');
            Route::middleware(['role:categories.create'])->post('/create', 'CategoriesController@adminStoreAction');
            Route::middleware(['role:categories.delete'])->post('/delete/{id}', 'CategoriesController@adminDeleteAction');
            Route::middleware(['role:categories.read'])->get('/edit/{id}', 'CategoriesController@adminEditAction');
            Route::middleware(['role:categories.write'])->post('/edit/{id}', 'CategoriesController@adminUpdateAction');
            Route::middleware(['role:seo.write'])->post('/seo/{id}', 'CategoriesController@adminUpdateSeoAction');
            Route::middleware(['role:categories.write'])->post('/change_status/{id}', 'CategoriesController@adminUpdateStatusAction');
            Route::middleware(['role:categories.read'])->post('/children/{id}', 'CategoriesController@adminChildrenAction');
            Route::middleware(['role:categories.read'])->get('/livesearch', 'CategoriesController@adminLivesearchAction');
            Route::middleware(['role:categories.read'])->post('/tree', 'CategoriesController@adminTreeAction');
        });
        Route::group(['prefix' => 'attributes'], function(){
            Route::middleware(['role:attributes.read'])->get('/', 'AttributesController@adminIndexAction');
            Route::middleware(['role:categories.read'])->post('/list', 'AttributesController@adminListAction');
            Route::middleware(['role:attributes.create'])->post('/create', 'AttributesController@adminStoreAction');
            Route::middleware(['role:attributes.delete'])->post('/delete/{id}', 'AttributesController@adminDeleteAction');
            Route::middleware(['role:attributes.read'])->get('/edit/{id}', 'AttributesController@adminEditAction');
            Route::middleware(['role:attributes.write'])->post('/edit/{id}', 'AttributesController@adminUpdateAction');
            Route::middleware(['role:attributes.write'])->post('/filter/change_status/{id}', 'AttributesController@adminUpdateFilterStatusAction');
            Route::middleware(['role:attributes.write'])->post('/values/{id}', 'AttributesController@adminUpdateValuesAction');
            Route::group(['prefix' => 'api'], function(){
                Route::middleware(['role:attributes.read'])->post('/values/{id}', 'AttributesController@adminAttributeValuesApi');
            });
        });
        // Роуты промокодов перенесены в Modules/Coupons/routes/web.php
        Route::group(['prefix' => 'exports'], function(){
            Route::middleware(['role:exports.read'])->any('/', 'ExportsController@adminIndexAction');
            Route::middleware(['role:exports.read'])->post('/list', 'ExportsController@adminListAction');
            Route::middleware(['role:exports.create'])->get('/create', 'ExportsController@adminCreateAction');
            Route::middleware(['role:exports.create'])->post('/create', 'ExportsController@adminStoreAction');
            Route::middleware(['role:exports.read'])->get('/edit/{id}', 'ExportsController@adminEditAction');
            Route::middleware(['role:exports.write'])->post('/{id}/update_settings', 'ExportsController@adminUpdateSettingsAction');
            Route::middleware(['role:exports.write'])->post('/{id}/update_fields', 'ExportsController@adminUpdateFieldsAction');
            Route::middleware(['role:exports.write'])->post('/{id}/update_filter', 'ExportsController@adminUpdateFilterAction');
            Route::middleware(['role:exports.delete'])->post('/delete/{id}', 'ExportsController@adminDestroyAction');
            Route::middleware(['role:exports.read'])->get('/download/{id}', 'ExportsController@adminDownloadAction');
            Route::middleware(['role:exports.read'])->post('/{id}/refresh', 'ExportsController@adminRefreshAction');
        });
        Route::group(['prefix' => 'imports'], function(){
            Route::middleware(['role:imports.read'])->get('/', 'ImportsController@adminImportAction');
            Route::middleware(['role:imports.read'])->post('/list', 'ImportsController@adminListAction');
            Route::middleware(['role:imports.create'])->post('/upload', 'ImportsController@adminUploadImportFileAction');
            Route::middleware(['role:imports.write'])->get('/edit/{id}', 'ImportsController@adminEditImportAction');
            Route::middleware(['role:imports.write'])->post('/{id}/update_settings', 'ImportsController@adminUpdateSettingsAction');
            Route::middleware(['role:imports.write'])->post('/{id}/update_fields', 'ImportsController@adminUpdateFieldsAction');
            Route::middleware(['role:imports.delete'])->post('/delete/{id}', 'ImportsController@adminDestroyImportAction');
            Route::middleware(['role:imports.write'])->post('/next_import_step/{id}', 'ImportsController@adminNextImportStepAction');
            Route::middleware(['role:imports.write'])->post('/refresh_import/{id}', 'ImportsController@adminRefreshImportAction');
            Route::middleware(['role:imports.write'])->post('/update_file/{id}', 'ImportsController@updateImportFileAction');
        });
        Route::group(['prefix' => 'redis'], function(){
            Route::middleware(['role:redis.write'])->get('/', 'ProductsController@adminRedisSyncAction');
            Route::middleware(['role:redis.write'])->post('/progress', 'ProductsController@adminRedisSyncProgress');
        });
    });

    // Роуты статей блога перенесены в Modules/Blog/routes/web.php

//    Route::group(['prefix' => 'news'], function(){
//        Route::middleware(['role:news.read'])->get('/', 'NewsController@adminIndexAction');
//        Route::middleware(['role:news.read'])->post('/list', 'NewsController@adminListAction');
//        Route::middleware(['role:news.create'])->post('/create', 'NewsController@adminStoreAction');
//        Route::middleware(['role:news.view'])->get('/edit/{id}', 'NewsController@adminEditAction');
//        Route::middleware(['role:news.write'])->post('/edit/{id}', 'NewsController@adminUpdateAction');
//        Route::middleware(['role:news.delete'])->get('/delete/{id}', 'NewsController@adminDestroyAction'); //softDelete
//        Route::middleware(['role:news.create,news.write'])->post('/add_product', 'AjaxController@adminAddNewsProduct');
//        Route::middleware(['role:news.create,news.write'])->post('/remove_product', 'AjaxController@adminRemoveNewsProduct');
//    });

    // Роуты категорий контента перенесены в Modules/Blog/routes/web.php

    Route::group(['prefix' => 'pages'], function(){
        Route::middleware(['role:pages.read'])->get('/', 'PagesController@adminIndexAction');
        Route::middleware(['role:pages.read'])->post('/list', 'PagesController@adminListAction');
        Route::middleware(['role:pages.create'])->post('/create', 'PagesController@adminStoreAction');
        Route::middleware(['role:pages.read'])->get('/edit/{id}', 'PagesController@adminEditAction');
        Route::middleware(['role:pages.write'])->post('/edit/{id}', 'PagesController@adminUpdateAction');
        Route::middleware(['role:seo.write'])->post('/seo/{id}', 'PagesController@adminUpdateSeoAction');
        Route::middleware(['role:pages.write'])->post('/change_status/{id}', 'PagesController@adminUpdateStatusAction');
        Route::middleware(['role:pages.delete'])->post('/delete/{id}', 'PagesController@adminDestroyAction');
        Route::middleware(['role:pages.read'])->post('/templates/list', 'PagesController@adminTemplatesListAction');
        Route::middleware(['role:pages.create,pages.write'])->get('/templates', 'PagesController@adminTemplatesAction');
        Route::middleware(['role:pages.create,pages.write'])->get('/template/{name}', 'PagesController@adminTemplateAction');
        Route::middleware(['role:pages.create,pages.write'])->post('/template/{name}', 'PagesController@adminUpdateTemplateAction');
        Route::middleware(['role:blocks.create,blocks.write'])->post('/template/fields/{name}', 'PagesController@adminUpdateTemplateFieldsAction');
        Route::middleware(['role:blocks.create,blocks.write'])->post('/template/file/{name}', 'PagesController@adminUpdateTemplateFileAction');
    });

    Route::group(['prefix' => 'blocks'], function(){
        Route::middleware(['role:blocks.read'])->get('/', 'BlocksController@adminIndexAction');
        Route::middleware(['role:blocks.read'])->post('/list', 'BlocksController@adminListAction');
        Route::middleware(['role:blocks.create'])->post('/create', 'BlocksController@adminStoreAction');
        Route::middleware(['role:blocks.read'])->get('/edit/{id}', 'BlocksController@adminEditAction');
        Route::middleware(['role:blocks.write'])->post('/edit/{id}', 'BlocksController@adminUpdateAction');
        Route::middleware(['role:blocks.delete'])->post('/delete/{id}', 'BlocksController@adminDestroyAction');
        Route::middleware(['role:blocks.create,blocks.write'])->get('/templates', 'BlocksController@adminTemplatesAction');
        Route::middleware(['role:blocks.read'])->post('/templates/list', 'BlocksController@adminTemplatesListAction');
        Route::middleware(['role:blocks.create,blocks.write'])->get('/template/{name}', 'BlocksController@adminTemplateAction');
        Route::middleware(['role:blocks.create,blocks.write'])->post('/template/fields/{name}', 'BlocksController@adminUpdateTemplateFieldsAction');
        Route::middleware(['role:blocks.create,blocks.write'])->post('/template/file/{name}', 'BlocksController@adminUpdateTemplateFileAction');
    });

    Route::group(['prefix' => 'media'], function(){
        Route::middleware(['role:media.read'])->get('/', 'MediaController@adminIndexAction');
        Route::middleware(['role:media.read'])->get('/trash', 'MediaController@adminTrashAction');
        Route::middleware(['role:media.read'])->post('/ajax', 'MediaController@adminAjaxAction');
        Route::middleware(['role:media.create'])->post('/upload', 'MediaController@adminUploadAction');
    });

    Route::middleware(['role:media.read'])->match(['get', 'post'], '/ajax', 'AjaxController@index');

//    Route::group(['prefix' => 'actions'], function(){
//        Route::middleware(['role:actions.read'])->get('/', 'ActionsController@index');
//        Route::middleware(['role:actions.view'])->get('/show/{id}', 'ActionsController@show');
//    });

//    Route::group(['prefix' => 'coupons'], function(){
//        Route::middleware(['role:coupons.read'])->get('/', 'CouponsController@adminIndexAction');
//        Route::middleware(['role:coupons.create'])->get('/create', 'CouponsController@adminCreateAction');
//        Route::middleware(['role:coupons.create'])->post('/generate_code', 'CouponsController@adminGenerateCodeAction');
//        Route::middleware(['role:coupons.create'])->post('/create', 'CouponsController@adminStoreAction');
//        Route::middleware(['role:coupons.view'])->get('/edit/{id}', 'CouponsController@adminEditAction');
//        Route::middleware(['role:coupons.write'])->post('/edit/{id}', 'CouponsController@adminUpdateAction');
//        Route::middleware(['role:coupons.write'])->post('/change_status/{id}', 'CouponsController@adminUpdateStatusAction');
//        Route::middleware(['role:coupons.delete'])->get('/delete/{id}', 'CouponsController@adminDeleteAction');
//    });

    Route::group(['prefix' => 'shop'], function(){
        Route::group(['prefix' => 'settings'], function(){
            Route::get('/', 'SettingsController@adminShopSettingsAction');
            Route::post('/delivery', 'SettingsController@adminSaveDeliveryShopSettingsAction');
            Route::post('/payment', 'SettingsController@adminSavePaymentShopSettingsAction');
            Route::post('/contacts', 'SettingsController@adminSaveContactsShopSettingsAction');
            Route::post('/trustpilot', 'SettingsController@adminSaveTrustpilotSettingsAction');
            Route::post('/mycryptocheckout', 'SettingsController@adminSaveMyCryptoCheckoutSettingsAction');
            Route::post('/crypto-wallets', 'SettingsController@saveCryptoWallet');
            Route::delete('/crypto-wallets/{id}', 'SettingsController@deleteCryptoWallet');
            Route::post('/maintenance/down', 'MaintenanceController@down');
            Route::post('/maintenance/up', 'MaintenanceController@up');
            Route::post('/maintenance/settings', 'MaintenanceController@saveSettings');
            Route::post('/modules', 'SettingsController@adminSaveModulesSettingsAction');
        });
    });

    Route::group(['prefix' => 'users'], function(){
        Route::middleware(['role:users.read'])->get('/', 'UsersController@adminIndexAction');
        Route::middleware(['role:users.read'])->post('/list', 'UsersController@adminListAction');
        Route::middleware(['role:users.create'])->get('/create', 'UsersController@adminCreateAction');
        Route::middleware(['role:users.create'])->post('/create', 'UsersController@adminStoreAction');
        Route::middleware(['role:users.write'])->get('/edit/{id}', 'UsersController@adminEditAction');
        Route::middleware(['role:seo.write'])->post('/seo/{id}', 'UsersController@adminUpdateSeoAction');
        Route::middleware(['role:users.write'])->post('/update_profile/{id}', 'UsersController@adminUpdateProfileAction');
        Route::middleware(['role:users.write'])->post('/update_information/{id}', 'UsersController@adminUpdateInformationAction');
        Route::middleware(['role:users.write'])->post('/update_password/{id}', 'UsersController@adminUpdatePasswordAction');
        Route::middleware(['role:users.write'])->post('/change_photo/{id}', 'UsersController@adminChangePhotoAction');
        Route::middleware(['role:users.read'])->get('/show/{id}', 'UsersController@adminShowAction');
        Route::middleware(['role:users.delete'])->get('/delete/{id}', 'UsersController@destroy'); //softDelete
        Route::get('/profile', 'UsersController@adminProfileAction');
        Route::post('/profile', 'UsersController@adminUpdateProfileAction');
        Route::middleware(['role:users.read'])->get('/export', 'UsersController@export');
        Route::middleware(['role:users.create'])->post('/import', 'UsersController@import');
    });

//    Route::middleware(['role:users.read,users.read.managers'])->get('/managers', 'UserController@managers');
//    Route::middleware(['role:users.read,users.read.moderators'])->get('/moderators', 'UserController@moderators');
//    Route::middleware(['role:users.read,users.read.marketers'])->get('/marketers', 'UserController@marketers');

    Route::group(['prefix' => 'orders'], function(){
        Route::middleware(['role:orders.read'])->get('/', 'OrdersController@adminIndexAction');
        Route::middleware(['role:orders.read'])->post('/list', 'OrdersController@adminListAction');
        Route::middleware(['role:orders.write'])->post('/update-tracking/{id}', 'OrdersController@updateTracking');
        Route::middleware(['role:orders.create'])->get('/create', 'OrdersController@create');
        Route::middleware(['role:orders.create'])->post('/create', 'OrdersController@store');
        Route::middleware(['role:orders.read'])->get('/edit/{id}', 'OrdersController@edit');
        Route::middleware(['role:orders.write'])->post('/edit/{id}', 'OrdersController@adminUpdateAction');
        Route::middleware(['role:orders.write'])->post('/edit/{id}/add_products', 'OrdersController@addProducts');
        Route::middleware(['role:orders.write'])->post('/edit/{id}/change_qty', 'OrdersController@changeQty');
        Route::middleware(['role:orders.write'])->post('/product/delete/{id}', 'OrdersController@removeProduct');
        Route::middleware(['role:orders.delete'])->post('/delete/{id}', 'OrdersController@adminDeleteAction'); //softDelete
        Route::middleware(['role:orders.read'])->get('/invoice/{id}', 'OrdersController@invoice');
        Route::middleware(['role:orders.write'])->post('/get_product_data', 'OrdersController@get_product_data');
        Route::middleware(['role:orders.write'])->post('/update_product_data', 'OrdersController@update_product_data');
    });

    Route::group(['prefix' => 'promotion'], function(){
        Route::middleware(['role:seo.read'])->get('/pages', 'SeoController@adminIndexAction');
        Route::middleware(['role:seo.read'])->post('/list', 'SeoController@adminListAction');
        Route::middleware(['role:seo.create'])->get('/create', 'SeoController@adminCreateAction');
        Route::middleware(['role:seo.create'])->post('/create', 'SeoController@adminStoreAction');
        Route::middleware(['role:seo.delete'])->post('/delete/{id}', 'SeoController@adminDeleteAction');
        Route::middleware(['role:seo.read'])->get('/edit/{id}', 'SeoController@adminEditAction');
        Route::middleware(['role:seo.write'])->post('/edit/{id}', 'SeoController@adminUpdateAction');
        Route::middleware(['role:seo.read'])->get('/settings', 'SeoController@seoSettingsAction');
        Route::middleware(['role:seo.write'])->post('/google', 'SeoController@adminUpdateGoogleSettingsAction');
        Route::middleware(['role:seo.write'])->post('/fb', 'SeoController@adminUpdateFacebookSettingsAction');
        Route::middleware(['role:seo.write'])->post('/microdata', 'SeoController@adminUpdateMicrodataSettingsAction');
        Route::middleware(['role:seo.write'])->post('/templates', 'SeoController@adminUpdateTemplateSettingsAction');
        Route::group(['prefix' => 'redirects'], function(){
            Route::middleware(['role:redirects.read'])->get('/', 'RedirectsController@adminIndexAction');
            Route::middleware(['role:redirects.read'])->post('/list', 'RedirectsController@adminListAction');
            Route::middleware(['role:redirects.create'])->post('/create', 'RedirectsController@adminStoreAction');
            Route::middleware(['role:redirects.delete'])->post('/delete/{id}', 'RedirectsController@adminDeleteAction');
            Route::middleware(['role:redirects.read'])->get('/edit/{id}', 'RedirectsController@adminEditAction');
            Route::middleware(['role:redirects.write'])->post('/edit/{id}', 'RedirectsController@adminUpdateAction');
        });
    });

    Route::group(['prefix' => 'settings'], function(){
        Route::middleware(['role:coupons.read'])->get('/', 'SettingsController@adminShopSettingsAction');
        Route::middleware(['role:coupons.read'])->post('/delivery', 'SettingsController@adminSaveDeliveryShopSettingsAction');
        Route::middleware(['role:coupons.read'])->post('/payment', 'SettingsController@adminSavePaymentShopSettingsAction');
        Route::middleware(['role:coupons.read'])->post('/contacts', 'SettingsController@adminSaveContactsShopSettingsAction');
    });

    // Роуты отзывов (products/site) перенесены в Modules/Reviews/routes/web.php

//    Route::group(['prefix' => 'shopreviews'], function(){
//        Route::middleware(['role:shopreviews.read'])->get('/', 'ShopReviewsController@index');
//        Route::middleware(['role:shopreviews.view'])->get('/show/{id}', 'ShopReviewsController@show');
//        Route::middleware(['role:shopreviews.write'])->post('/show/{id}', 'ShopReviewsController@update');
//        Route::middleware(['role:shopreviews.delete'])->get('/delete/{id}', 'ShopReviewsController@destroy'); //softDelete
//    });

    Route::group(['prefix' => 'requests'], function(){
        Route::get('/', 'RequestsController@adminIndexAction');
        Route::post('/list', 'RequestsController@adminListAction');
        Route::post('/delete/{id}', 'RequestsController@adminDestroyAction');
    });
});

/**
 * API routing
 */
Route::middleware(['api'])->prefix('api')->group(function(){
    Route::post('/save_ajax_error', 'ErrorsController@saveAjaxErrorAction');
});

/**
 * Frontend routing
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
        // /telegram_api перенесён в Modules/Notifications/routes/web.php
        /**
         * Service routing
         */
        Route::post('/api/mcc_callback{slash?}', 'ApiController@mcc_callback')->where('slash', '/?');
        Route::post('/api/webhooks/btcpay', 'ApiController@btcpay_callback');
        Route::get('/api/{method?}', 'ApiController@index');
        Route::get('/ajax/mcc-init', 'AjaxController@mccInit');
        Route::post('/ajax/{method}', 'AjaxController@front');
        Route::post('/products/filter', 'CatalogController@filterAction');
        Route::post('/product_popup', 'ProductsController@popupAction');
        Route::post('/cart/update','CartController@updateCart');
        Route::post('/cart/updateAll','CartController@update');
        Route::post('/cart/get','CartController@getCart');
        Route::post('/apply_coupon', 'CheckoutController@applyCoupon');
        Route::get('/livesearch', 'ProductsController@livesearch');
        Route::match(['get', 'post'], '/search/{page?}', ['as' => 'search', 'uses' => 'ProductsController@search']);
        // /shopreview/add и /review/add перенесены в Modules/Reviews/routes/web.php
        Route::post('/confirm_order_payment', 'OrdersController@confirmOrderPaymentAction');
        Route::post('/update_payment_method', 'CheckoutController@updatePaymentMethodAction');
        Route::post('/track_order', 'OrdersController@trackOrderAction');

        Route::get('/', ['as'=>'home', 'uses'=>'PagesController@indexAction']);

        /**
         * Authorization routing
         */
        Route::get('/login', 'AuthenticationController@loginAction');
        Route::post('/login', 'AuthenticationController@authenticate');
        Route::get('/logout', 'AuthenticationController@logoutAction');
        Route::post('/sendmail', 'ContactFormsController@sendForm');
        Route::get('/thanks', 'OrdersController@thanksAction');

        //Social Login
        Route::get('/login/{provider?}',[
            'uses' => 'AuthenticationController@getSocialAuth',
            'as'   => 'auth.getSocialAuth'
        ]);
        Route::get('/login/callback/{provider?}',[
            'uses' => 'AuthenticationController@getSocialAuthCallback',
            'as'   => 'auth.getSocialAuthCallback'
        ]);

        Route::group(['prefix' => 'sitemap'], function(){
            Route::get('/', 'SitemapController@index');
            Route::get('/categories/{page?}', 'SitemapController@categories');
            Route::get('/products/{page?}', 'SitemapController@products');
            Route::get('/pages/{page?}', 'SitemapController@pages');
            Route::get('/blog/{page?}', 'SitemapController@blog');
        });

        Route::match(['get', 'post'], '{url}', function($url){
            $data = new stdClass();
            $locales = config()->get('app.locales');
            app()->setLocale(config()->get('app.locale'));
            foreach($locales as $locale){
                if(substr($url, 0, 3) == $locale.'/'){
                    $url = substr($url, 3);
                    app()->setLocale($locale);
                }
            }
            $redirect = \App\Models\Redirect::where('old_url', '/'.$url)->first();
            if(!empty($redirect)){
                return redirect($redirect->new_url);
            }
            $seo = \App\Models\Seo::where('url', '/'.$url)->first();

            if(empty($seo)){
                $parts = explode('/', $url);
                if(count($parts) > 1){
                    $params = '';
                    foreach (array_reverse($parts) as $part){
                        $params = '/'.$part.$params;
                        $alias = preg_replace("/".str_replace('/', '\/', $params)."$/", '', '/'.$url);
                        $seo = \App\Models\Seo::where('url', $alias)->first();
                        if(!empty($seo)){
                            break;
                        }
                    }
                }

                if(empty($seo)){
                    abort(404);
                }else{
                    $data->params = explode('/', trim($params, '/'));
                }
            }

            $data->seo = $seo;
            $data->request = Request();

            // Страницы, чей seotable_type принадлежит отключённому модулю, недоступны,
            // даже если сама SEO-запись всё ещё есть в базе.
            $moduleSeotableTypes = [
                'Blog' => 'blog',
                'ContentCategories' => 'blog',
            ];
            if(isset($moduleSeotableTypes[$seo->seotable_type]) && !module_active($moduleSeotableTypes[$seo->seotable_type])){
                abort(404);
            }

            $controllers = [
                'Page' => 'Pages',
                'Service' => 'Services',
                'App\Models\Product' => 'Products',
            ];

            // Контроллеры, вынесенные в модули, резолвятся в своём namespace;
            // остальные — как раньше, в App\Http\Controllers.
            $moduleControllers = [
                'Blog' => \Modules\Blog\Http\Controllers\BlogController::class,
                'ContentCategories' => \Modules\Blog\Http\Controllers\ContentCategoriesController::class,
            ];

            $controllerClass = $moduleControllers[$seo->seotable_type]
                ?? ('\App\Http\Controllers\\' . (isset($controllers[$seo->seotable_type]) ? $controllers[$seo->seotable_type] : $seo->seotable_type) . 'Controller');

            $controller = app()->make($controllerClass);
            return $controller->callAction($seo->action, ['data' => $data]);

        })->where('url', '([A-Za-z0-9А-Яа-я\-_\/,;"\' ]+)');
    });
}
