<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Models\Review;
use App\Models\SiteReview;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {   // get all data from menu.json file
        $verticalMenuJson = file_get_contents(base_path('resources/data/menus/admin-menu.json'));
        $verticalMenuData = json_decode($verticalMenuJson);

        foreach($verticalMenuData->menu as $i => &$item){
            if(isset($item->url) && $item->url == '/admin/orders'){
                $orders_count = Order::where('status_id', 1)->count();
                if($orders_count){
                    $item->tag = $orders_count;
                }
            }
            if(isset($item->url) && $item->url == '/admin/reviews'){
                $site_reviews_count = SiteReview::where('new', 1)->count();
                $products_reviews_count = Review::where('new', 1)->count();
                $reviews_count = $site_reviews_count + $products_reviews_count;
                if($reviews_count){
                    $item->tag = $reviews_count;
                    foreach($item->submenu as $i => &$subitem){
                        if(isset($subitem->url) && $subitem->url == '/admin/reviews/products'){
                            if($products_reviews_count){
                                $subitem->tag = $products_reviews_count;
                            }
                        }
                        if(isset($subitem->url) && $subitem->url == '/admin/reviews/site'){
                            if($site_reviews_count){
                                $subitem->tag = $site_reviews_count;
                            }
                        }
                    }
                }
            }

//            if(isset($item->url) && $item->url == '/admin/blocks'){
//                unset($verticalMenuData->menu[$i]);
//            }
        }

        // share all menuData to all the views
        \View::share('menuData',[$verticalMenuData]);
    }
}
