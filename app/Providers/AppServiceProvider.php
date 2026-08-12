<?php

namespace App\Providers;

use App\Models\AttributeValue;
use Modules\Blog\Models\Blog;
use Modules\Blog\Models\ContentCategory;
use App\Models\Product;
use Modules\Reviews\Models\SiteReview;
use Nekhbet\LaravelGettext\Facades\LaravelGettext;
use Illuminate\Database\Eloquent\Relations\Relation;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\ServiceProvider;
use App\Models\Setting;
use App\Models\User;
use App\Helpers\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use App\Helpers\Fields;
use App\Models\File;
use App\Models\Order;
use Modules\Reviews\Models\Review;
use App\Models\Cart;
use App\Models\Menu;
use App;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    private $user;
    private $roles_array = array();

    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        // Автобиндинг ACF-подобных полей страниц/блоков в шаблонах: @field('slug')
        // читает значение из ambient-переменной $fields, не требуя ручной вставки
        // Blade-сниппета из кнопки "Generate" в админке шаблонов. Регистрируется
        // до раннего возврата для консоли, т.к. нужна и для `artisan view:cache`.
        Blade::directive('field', function($expression){
            // Полное имя класса обязательно: скомпилированный blade-файл выполняется без
            // namespace/use — короткое "Fields::" резолвится в global-неймспейс и падает
            // с "Class Fields not found" (поймали на живом рендере страницы, не только на
            // синтаксической проверке компиляции — та этого не ловит).
            return "<?php echo \\App\\Helpers\\Fields::value(\$fields ?? [], {$expression}); ?>";
        });

        if(app()->runningInConsole()){
            return;
        }

        // Проверка БД
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            abort(503);
        }
        if(!Schema::hasTable('settings')){
            abort(503);
        }

        Relation::morphMap([
            'Pages' => \App\Models\Page::class,
            'Services' => App\Models\Service::class,
            'Seo' => \App\Models\Seo::class,
            'Blog' => Blog::class,
            'News' => \App\Models\News::class,
            'Cases' => \App\Models\Cases::class,
            'Blocks' => App\Models\Block::class,
            'ContentCategories' => ContentCategory::class,
            'Users' => App\Models\User::class,
            'AuditSteps' => App\Models\AuditStep::class,
            'Products' => App\Models\Product::class,
            'Categories' => App\Models\Category::class,
            'Attributes' => App\Models\Attribute::class,
            'AttributeValues' => App\Models\AttributeValue::class,
            'Menus' => App\Models\Menu::class,
            'MenuItems' => App\Models\MenuItem::class,
            'Files' => App\Models\File::class,
        ]);

        $user = Sentinel::getUser();
        if (!is_null($user)) {
            $user = User::find($user->id);
        }

        $this->user = $user;

        $lgl = Config::get('app.locales_gettext');
        $locale = Config::get('app.locale');

        if($request->segment(1) === 'admin'){
            if(!is_null($user) && !empty($user->language)){
                $locale = $user->language;
            }

            if(isset($lgl[$locale])){
                LaravelGettext::setLocale($lgl[$locale]);
            }
        }else{
            $locales = Config::get('app.locales');
            foreach($locales as $loc){
                if($request->segment(1) == $loc){
                    $locale = $loc;
                    if(isset($lgl[$loc])){
                        LaravelGettext::setLocale($lgl[$loc]);
                    }
                    break;
                }
            }
        }

        if($locale === Config::get('app.locale') && isset($lgl[$locale])){
            LaravelGettext::setLocale($lgl[$locale]);
        }
        app()->setLocale($locale);

        if (!is_null($user)) {
            view()->composer([
                'admin.layouts.main',
            ], function ($view) use ($user) {
                $view->with([
                    'me' => $user
                ]);
            });

            view()->composer([
                'admin.*',
            ], function ($view) use ($user) {
                $view->with([
                    'me' => $user
                ]);
            });

            if ($this->user) {
                $roles = Sentinel::getRoles()->toArray();
                foreach ($roles as $role) {
                    $this->roles_array[] = $role['slug'];
                }
            }

            view()->composer(['admin.media.assets'], function ($view) {
                $files = new File();
                $total = disk_total_space('/');
//                $total = 10737418240;
                $free = disk_free_space('/');
//                $free = 5368709120;
                $total_space = $files->sizeFormat($total);
                $used_space = $files->sizeFormat($total - $free);
                $percent_space = ($total - $free) * 100 / $total;

                $view->with($this->mediaVariables())
                    ->with('total_space', $total_space)
                    ->with('used_space', $used_space)
                    ->with('percent_space', $percent_space);
            });

            view()->composer(['admin.products.create', 'admin.seo_settings'], function ($view){
                $view->with('languages', Config::get('app.locales_names'));
            });

            view()->composer([
                'admin.layouts.main',
                'admin.sales.index',
                'admin.sales.edit',
                'admin.sales.products',
                'admin.media.index',
                'admin.pages.index',
                'admin.pages.edit',
                'admin.blocks.index',
                'admin.blocks.edit',
                'admin.news.index',
                'admin.news.edit',
                'admin.reviews.index',
                'admin.reviews.show',
                'admin.users.index',
                'admin.extra_settings',
                'admin.seo_settings',
                'admin.seo.index',
                'admin.seo.edit',
                'admin.seo.redirects.index',
                'admin.seo.redirects.edit',
            ], function ($view) use ($user){
                $view->with([
                    'user' => $user,
                ]);
            });

            view()->composer([
                'admin.users.edit',
            ], function ($view) use ($user){
                $view->with([
                    'me' => $user,
                ]);
            });
        }

        view()->composer([
            'public/*',
            'users/*',
            'errors/*',
            'index',
            'login',
            'admin/layouts/*',
            'registration',
            'forgotten'
        ], function ($view) use ($user) {
            $settings = new Setting;
            $view->with([
                'settings' => $settings->get_global(),
                'user' => $user ? $user : false
            ]);
        });

        view()->composer([
            'admin.pages.fields.product',
            'admin.blocks.fields.product', // раньше отсутствовало — $products не долетал до блоков
        ], function ($view) use ($user, $locale) {
            $view->with([
                'products' => App\Models\Product::with(['localization' => function($query) use($locale){
                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])
                        ->where('language', $locale)
                        ->where('field', 'name');
                }])->get()
            ]);
        });

        view()->composer([
            'admin.pages.fields.relationship',
            'admin.blocks.fields.relationship',
        ], function ($view) use ($locale) {
            $view->with([
                'pages' => App\Models\Page::with(['localization' => function($query) use($locale){
                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])
                        ->where('language', $locale)
                        ->where('field', 'name');
                }])->get()
            ]);
        });

        view()->composer([
            'admin.pages.fields.taxonomy',
            'admin.blocks.fields.taxonomy',
        ], function ($view) use ($locale) {
            // Category не даёт простого ->name как Page/Block/Product (см. Category::getOptimizedName) —
            // читаем локализации напрямую, не трогая её оптимизированный кэширующий accessor.
            $names = App\Models\Localization::where('localizable_type', 'Categories')
                ->where('language', $locale)
                ->where('field', 'name')
                ->pluck('value', 'localizable_id');

            $categories = App\Models\Category::orderBy('id')->pluck('id')->map(function($id) use ($names){
                return (object)['id' => $id, 'name' => $names->get($id, '#'.$id)];
            });

            $view->with(['categories' => $categories]);
        });

        view()->composer(['admin.layouts.sidebar', 'admin.layouts.main'], function ($view) {
            $view->with('new_site_reviews', module_active('reviews') ? SiteReview::where('new', 1)->get() : collect());
        });

        view()->composer(['public.layouts.site_reviews', 'public.layouts.microdata.category'], function ($view){
            if(strpos(request()->segment(2),'page-') !== false){
                $page = (int) str_replace('page-', '', request()->segment(2));
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($page) {
                    return $page;
                });
            }

            $view->with('site_reviews', module_active('reviews') ? SiteReview::where('published', 1)
                ->orderBy('created_at', 'desc')
                ->with('user')
                ->paginate(16) : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 16));
        });

        view()->composer(['public.layouts.pages.reviews'], function ($view){
            $view->with('reviews_count', module_active('reviews') ? SiteReview::where('published', 1)->count() : 0)
                ->with('reviews_grade', module_active('reviews') ? SiteReview::where('published', 1)->avg('grade') : null);
        });

        view()->composer([
            'public.layouts.header'
        ], function ($view) {
            // При выключенном cart_checkout не создаём/не трогаем корзину вовсе — иначе
            // current_cart() молча создаёт пустую строку в БД для каждого нового посетителя
            // каждой страницы, даже если корзины на сайте вообще нет.
            $current_cart = module_active('cart_checkout') ? (new Cart)->current_cart() : null;
            $main_menu = Menu::find(2);
            $settings = new Setting;
            $compare_groups = module_active('compare') ? app(\Modules\Compare\Services\CompareService::class)->groupsSummary() : collect();
            $view->with('cart', $current_cart)
                ->with('main_menu', !empty($main_menu) ? $main_menu->links : null)
                ->with('compare_groups', $compare_groups)
                ->with('compare_count', $compare_groups->sum('count'))
                ->with('site_message', $settings->get_setting('site_message_enabled') ? $settings->get_setting('site_message_'.app()->getLocale()) : null);
        });

        view()->composer([
            'public.layouts.footer'
        ], function ($view) {
            $footer_menu = Menu::find(1);
            $view->with('footer_menu', !empty($footer_menu) ? $footer_menu->links : null);
        });

        view()->composer([
            'public.layouts.cart'
        ], function ($view) {
            // Партиал подключается и из шапки (там $cart уже приходит из её композера),
            // и из офканваса в футере (где $cart никто не передаёт) — считаем корзину
            // только если она ещё не была передана явно, чтобы не дублировать запрос.
            if($view->offsetExists('cart')){
                return;
            }
            $view->with('cart', module_active('cart_checkout') ? (new Cart)->current_cart() : null);
        });

        view()->composer([
            'public.layouts.pagination',
            'public.layouts.head',
            'public.category'
        ], function ($view) {
            $view->with('cp', new Paginator());
        });

        view()->composer([
            'public.layouts.microdata.local_business',
            'public.layouts.main',
            'public.layouts.header'
        ], function ($view) {
            $settings = new Setting;
            $settings = $settings->get_global();
            $view->with([
                'settings' => $settings,
                'logo' => empty($settings->ld_image) ? File::find(1) : File::find($settings->ld_image)
            ]);
        });

        view()->composer([
            'admin.pages.fields',
            'admin.pages.fields.*',
            'admin.blocks.fields',
            'admin.blocks.fields.*',
            'admin.layouts.seo',
            'admin.layouts.main',
            'admin.seo_settings',
            'admin.layouts.form.*',
        ], function ($view) {
            $view->with('locales_names', Config::get('app.locales_names'))
                ->with('main_lang', Config::get('app.locale'));
        });

    }

    public function convert_hr_to_bytes( $value ) {
        $value = strtolower( trim( $value ) );
        $bytes = (int) $value;

        if ( false !== strpos( $value, 'g' ) ) {
            $bytes *= 1024*1024*1024;
        } elseif ( false !== strpos( $value, 'm' ) ) {
            $bytes *= 1024*1024;
        } elseif ( false !== strpos( $value, 'k' ) ) {
            $bytes *= 1024;
        }

        // Deal with large (float) values which run into the maximum integer size.
        return min( $bytes, PHP_INT_MAX );
    }

    public function mediaVariables()
    {
        $user = Sentinel::getUser();
        $uid = 0;
        if (!is_null($user)) {
            $uid = $user->id;
        }

        $files = new File();
        $u_bytes = $this->convert_hr_to_bytes(ini_get('upload_max_filesize'));
        $p_bytes = $this->convert_hr_to_bytes(ini_get('post_max_size'));

        $max_upload_size = min($u_bytes, $p_bytes);

        if (!$max_upload_size) {
            $max_upload_size = 0;
        }

        $max_size = $files->sizeFormat($max_upload_size);
        $commonL10n = json_encode(trans('common'), JSON_UNESCAPED_UNICODE);
        $pluploadL10n = json_encode(trans('plupload'), JSON_UNESCAPED_UNICODE);
        $quicktagsL10n = json_encode(trans('quicktags'), JSON_UNESCAPED_UNICODE);
        $thickboxL10n = json_encode(trans('thickbox'), JSON_UNESCAPED_UNICODE);

        $_wpMediaViewsL10n = trans('wpMediaViews');
        $_wpMediaViewsL10n['settings'] = array_merge($_wpMediaViewsL10n['settings'], [
            'tabs' => [],
            'tabUrl' => '/admin/media-upload?chromeless=1',
            'mimeIcons' => [
                'image' => 'image.svg',
                'svg' => 'pencil.svg',
                'document' => 'morph-doc.svg',
                'video' => 'camcoder.svg',
                'audio' => 'music.svg',
                'archive' => 'servers.svg',
            ],
            'captions' => '1',
            'nonce' => [
                'sendToEditor' => '091a1773c8',
            ],
            'post' => [
                'id' => '0',
            ],
            'defaultProps' => [
                'link' => 'none',
                'align' => '',
                'size' => '',
            ],
            'attachmentCounts' => [
                'audio' => File::where('type', 'audio')->count(),
                'video' => File::where('type', 'video')->count(),
                'image' => File::where('type', 'image')->count(),
                'all' => File::count(),
//                    'important' => File::leftJoin('user_important_files', 'user_important_files.file_id', '=', 'files.id')
//                        ->where('user_important_files.user_id', $uid)
//                        ->count(),
                'trash' => File::onlyTrashed()->count()
            ],
            'oEmbedProxyUrl' => '/wp-json/oembed/1.0/proxy',
            'embedExts' => [
                'mp3',
                'ogg',
                'flac',
                'm4a',
                'wav',
                'mp4',
                'm4v',
                'webm',
                'ogv',
                'flv',
            ],
            'embedMimes' => [
                'mp3' => 'audio/mpeg',
                'ogg' => 'audio/ogg',
                'flac' => 'audio/flac',
                'm4a' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'mp4' => 'video/mp4',
                'm4v' => 'video/mp4',
                'webm' => 'video/webm',
                'ogv' => 'video/ogg',
                'flv' => 'video/x-flv',
            ],
            'contentWidth' => '',
            'months' => [
                [
                    'year' => date('Y'),
                    'month' => date('m'),
                    'text' => trans('date.month_declensions.' . date('F')) . date(' Y'),
                ]
            ],
            'mediaTrash' => '0',
            'counts' => [

            ]
        ]);

        $months_data = $files->select(DB::raw('created_at, YEAR( created_at ) AS year, MONTH( created_at ) AS month'))->distinct()->orderBy('created_at', 'DESC')->get();
        if (!empty($months_data)) {
            $months_names = trans('months');
            $months = [];
            foreach ($months_data as $month_year) {
                if (isset($months_names[$month_year->month])) {
                    $months[$month_year->month . '.' . $month_year->year] = [
                        'year' => $month_year->year,
                        'month' => $month_year->month,
                        'text' => sprintf(__('%1$s %2$d'), $months_names[$month_year->month], $month_year->year)
                    ];
                }
            }
            $_wpMediaViewsL10n['settings']['months'] = $months;
        }

        $wpUtilSettings = json_encode(['ajax' => ['url' => '/admin/ajax']], JSON_UNESCAPED_UNICODE);

        $wpMediaModelsL10n = json_encode([
            'settings' => [
                'ajaxurl' => '\/admin\/ajax',
                'post' => ['id' => 0]
            ]
        ], JSON_UNESCAPED_UNICODE);

        $uiAutocompleteL10n = json_encode(trans('uiAutocomplete'), JSON_UNESCAPED_UNICODE);

        $wpLinkL10n = json_encode(trans('wpLink'), JSON_UNESCAPED_UNICODE);

        $wpColorPickerL10n = json_encode(trans('wpColorPicker'), JSON_UNESCAPED_UNICODE);

        $authcheckL10n = json_encode([
            'beforeunload' => trans('locale.authcheck_beforeunload'),
            'interval' => 180
        ], JSON_UNESCAPED_UNICODE);

        $attachMediaBoxL10n = json_encode([
            'error' => trans('locale.attachMediaBox_error')
        ], JSON_UNESCAPED_UNICODE);

        $imageEditL10n = json_encode([
            'error' => trans('locale.imageEdit_error')
        ], JSON_UNESCAPED_UNICODE);

        $mceViewL10n = json_encode([
            'shortcodes' => [
                'wp_caption',
                'caption',
                'gallery',
                'playlist',
                'audio',
                'video',
                'embed',
                'acf',
                'toc',
                'no_toc',
                'sitemap',
                'sitemap_pages',
                'sitemap_categories',
                'sitemap_posts',
                'ratings',
                'contact-form-7',
                'contact-form',
                'wpseo_breadcrumb',
                'companies',
                'theme_of_the_week',
                'fav_company',
                'alert',
                'badge',
                'breadcrumb',
                'breadcrumb-item',
                'button',
                'button-group',
                'button-toolbar',
                'caret',
                'carousel',
                'carousel-item',
                'code',
                'collapse',
                'collapsibles',
                'column',
                'container',
                'container-fluid',
                'divider',
                'dropdown',
                'dropdown-header',
                'dropdown-item',
                'emphasis',
                'icon',
                'img',
                'embed-responsive',
                'jumbotron',
                'label',
                'lead',
                'list-group',
                'list-group-item',
                'list-group-item-heading',
                'list-group-item-text',
                'media',
                'media-body',
                'media-object',
                'modal',
                'modal-footer',
                'nav',
                'nav-item',
                'page-header',
                'panel',
                'popover',
                'progress',
                'progress-bar',
                'responsive',
                'row',
                'span',
                'tab',
                'table',
                'table-wrap',
                'tabs',
                'thumbnail',
                'tooltip',
                'well',
                'avatar',
                'avatar_upload',
            ]
        ], JSON_UNESCAPED_UNICODE);

        $tinymce = json_encode(trans('tinymce'), JSON_UNESCAPED_UNICODE);

        return [
            'max_size' => $max_size,
            'commonL10n' => $commonL10n,
            'wpUtilSettings' => $wpUtilSettings,
            'wpMediaModelsL10n' => $wpMediaModelsL10n,
            'pluploadL10n' => $pluploadL10n,
            'thickboxL10n' => $thickboxL10n,
            'quicktagsL10n' => $quicktagsL10n,
            'uiAutocompleteL10n' => $uiAutocompleteL10n,
            'wpLinkL10n' => $wpLinkL10n,
            'wpColorPickerL10n' => $wpColorPickerL10n,
            'authcheckL10n' => $authcheckL10n,
            'attachMediaBoxL10n' => $attachMediaBoxL10n,
            'imageEditL10n' => $imageEditL10n,
            'mceViewL10n' => $mceViewL10n,
            '_wpMediaViewsL10n' => json_encode($_wpMediaViewsL10n, JSON_UNESCAPED_UNICODE),
            'tinymce' => $tinymce
        ];
    }

    private function compactCategory($category)
    {
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'link' => $category->link(),
            'image' => !empty($category->image) ? $category->image->webp([510, 400], ['alt' => $category->name]) : ''
        ];

        if ($category->children_count) {
            $children = [];
            foreach ($category->children as $child) {
                $children[] = $this->compactCategory($child);
            }

            $data['children'] = $children;
        }

        return collect($data);
    }
}
