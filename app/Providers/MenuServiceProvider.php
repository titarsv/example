<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\SiteReview;

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
            if(isset($item->url) && $item->url == '/admin/reviews' && module_active('reviews')){
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

        $verticalMenuData->menu = $this->filterDisabledModuleItems($verticalMenuData->menu);

        // share all menuData to all the views
        \View::share('menuData',[$verticalMenuData]);
    }

    /**
     * Скрывает пункты меню отключённых модулей. Не трогает /admin/orders —
     * управление заказами остаётся доступно даже при выключенной публичной
     * корзине/чекауте (cart_checkout не имеет собственного раздела в меню).
     */
    private function disabledModuleMenuPrefixes(): array
    {
        $prefixes = [];

        if (!module_active('blog')) {
            $prefixes[] = '/admin/articles';
            $prefixes[] = '/admin/content/categories';
        }
        if (!module_active('reviews')) {
            $prefixes[] = '/admin/reviews';
        }
        if (!module_active('coupons')) {
            $prefixes[] = '/admin/products/promocodes';
        }
        if (!module_active('ai')) {
            $prefixes[] = '/admin/seo-content';
            $prefixes[] = '/admin/gemini-translate';
            $prefixes[] = '/admin/metadata';
        }

        return $prefixes;
    }

    private function filterDisabledModuleItems(array $items): array
    {
        $prefixes = $this->disabledModuleMenuPrefixes();

        if (empty($prefixes)) {
            return $items;
        }

        return $this->filterMenuItems($items, $prefixes);
    }

    private function filterMenuItems(array $items, array $prefixes): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (isset($item->url) && $this->isUrlHidden($item->url, $prefixes)) {
                continue;
            }

            if (isset($item->submenu) && is_array($item->submenu)) {
                $item->submenu = $this->filterMenuItems($item->submenu, $prefixes);
            }

            $filtered[] = $item;
        }

        return array_values($filtered);
    }

    private function isUrlHidden(string $url, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($url === $prefix || str_starts_with($url, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
