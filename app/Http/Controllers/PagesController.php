<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\Fields;
use App\Models\AttributeValue;
use Modules\Reviews\Models\SiteReview;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Action;
use App\Models\Product;
use App\Models\Page;
use Modules\Blog\Models\Blog;
use App\Models\Seo;
use App;

class PagesController extends Controller
{
    use \App\Http\Controllers\Concerns\DetectsMissingTemplates;
    use \App\Http\Controllers\Concerns\RecordsTemplateRevisions;

    protected $rules = [
        'name' => 'required|unique:pages'
    ];
    protected $messages = [];

    public function __construct(){
        $this->user = Sentinel::getUser();
        $this->messages = [
            'name.required' => trans('locale.This field must be filled!'),
            'name.unique' => trans('locale.The value must be unique!'),
            'body.required' => trans('locale.This field must be filled!')
        ];
    }

    public function indexAction(){
        if(Helper::isLighthouse()){
            $html = cache()->remember('home_page', 2592000, function(){
                return $this->homeHtml();
            });
        }else{
            $html = $this->homeHtml();
        }

//        $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
//        $currency_id = 'BTC';
//        $order = App\Models\Order::find(5);
//        $mcc->createPayment($order, $currency_id);
//        die();



        return response($html);
    }

    public function homeHtml(){
        $seo = Seo::where('url', '/')->first();
        $page = $seo->seotable;

        if(empty($page->status)){
            abort(404);
        }

        if($page->template == 'public.page'){
            $fields = null;
        }else{
            $d = $page->setFieldsCategories($page->setFieldsPages($page->setFieldsProducts($page->setFieldsImages(json_decode($page->localize(app()->getLocale(), 'body'))))));
            $fields = [];
            foreach($d as $field){
                if(in_array($field->type, ['repeater', 'group'])){
                    $fields[$field->slug] = $field->data;
                }else{
                    $fields[$field->slug] = isset($field->value) ? $field->value : '';
                }
            }
        }

        // Персональные рекомендации по истории просмотров (cookie "viewed"), с fallback
        // на популярность для анонимных/новых посетителей без истории (холодный старт)
        $viewed = json_decode(request()->cookie('viewed'), true);
        $recommended = !empty($viewed) ? Product::getPersonalizedRecommendations($viewed, 7) : collect();
        if ($recommended->isEmpty()) {
            $recommended = Product::orderBy('popularity', 'desc')->where('visible', 1)->limit(7)->get();
        }

        return view(empty($page->template) ? 'public.page' : $page->template)
            ->with('page', $page)
            ->with('fields', $fields)
            ->with('categories', Category::where('status', 1)->whereHas('image')->with('localization')->withCount('products')->get())
            ->with('articles', module_active('blog') ? Blog::orderBy('id', 'desc')->where('status', 1)->limit(7)->get() : collect())
            ->with('effects', AttributeValue::where('attribute_id', 1)->get())
            ->with('favorites', $recommended)
            ->with('reviews', module_active('reviews') ? SiteReview::orderBy('id', 'desc')->where('published', 1)->limit(7)->get() : collect())
            ->with('reviews_count', module_active('reviews') ? SiteReview::where('published', 1)->count() : 0)
            ->with('reviews_grade', module_active('reviews') ? SiteReview::where('published', 1)->avg('grade') : null)
            ->with('seo', $seo)
            ->withShortcodes()
            ->render();
    }

    /**
     * Отображение страницы
     *
     * @param $data
     *
     * @return $this
     */
    public function showAction($data){
        $page = $data->seo->seotable;

        if(empty($page->status)){
            abort(404);
        }

        if($page->template == 'public.page'){
            $fields = null;
        }else{
            $d = $page->setFieldsCategories($page->setFieldsPages($page->setFieldsProducts($page->setFieldsImages(json_decode($page->localize(App::getLocale(), 'body'))))));
            $fields = [];
            foreach($d as $field){
                if(in_array($field->type, ['repeater', 'group'])){
                    $fields[$field->slug] = $field->data;
                }else{
                    $fields[$field->slug] = isset($field->value) ? $field->value : '';
                }
            }
        }

        $seo = $data->seo;
        if(empty($seo->meta_title) || $seo->meta_title == $page->name){
            $seo->meta_title = $page->name . ' in UK';
        }
        if(empty($seo->meta_description) || $seo->meta_description == $page->name) {
            $seo->meta_description = $page->name;
        }

        return view(empty($page->template) ? 'public.page' : $page->template)
        ->with('page', $page)
        ->with('fields', $fields)
        ->with('seo', $seo)
        ->withShortcodes();
    }

    /**
     * Список страниц
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\View\View|object
     */
    public function adminIndexAction(){
        return view('admin.pages.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable'), 'js_messages' => trans('js_messages')])]);
    }

    /**
     * Фильтр страниц
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $query = Page::select('pages.*');

        if($request->has('search.value')){
            $locale = App::getLocale();
            $text = $request->search['value'];
            $query->leftJoin('localization', function($query) use($text, $locale){
                $query->on('pages.id', '=', 'localization.localizable_id')
                    ->where('field', 'name')
                    ->where('localizable_type', 'Blog')
                    ->where('language', $locale)
                    ->where('value', 'like', '%'.$text.'%');
            });
        }

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'], $order['dir']);
            }
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $pages = $query->get();

        $data = [];
        foreach($pages as $page){
            $actions = [];
            if($this->user->hasAccess(['pages.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/pages/edit/'.$page->id)
                ];
            }
            if($this->user->hasAccess(['pages.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $page->id,
                    'name' => $page->name
                ];
            }

            $data[] = [
                'id' => $page->id,
                'name' => ['link' => $page->link(), 'name' => $page->name],
                'status' => ['id' => $page->id, 'status' => (bool)$page->status],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Page::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Создание страницы
     *
     * @param Request $request
     * @param Page $pages
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStoreAction(Request $request, Page $pages){
        $validator = Validator::make($request->all(), ['name' => 'required'], ['name.required' => trans('locale.This field must be filled!')]);

        if($validator->fails()){
            return response()->json($validator);
        }

        $rd = new Request();
        $rd->merge(['name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => $request->name]);

        $id = $pages->insertGetId(['parent_id' => null, 'template' => 'public.page', 'status' => 0, 'sort_order' => 0]);
        $page = $pages->find($id);
        $page->saveSeo($rd);
        $page->saveLocalization($rd);

        Action::createEntity($page);

        return response()->json(['result' => 'success', 'redirect' => '/admin/pages/edit/'.$id]);
    }

    /**
     * Редактирование страницы
     *
     * @param $id
     * @param Page $pages
     *
     * @return mixed
     */
    public function adminEditAction($id, Page $pages, Setting $settings){
        $page = $pages->find($id);
        $templates = [(object)[
            'name' => 'page',
            'value' => 'public.page'
        ]];
        foreach(Storage::disk('local')->allFiles(theme_relative_path('views/public/layouts/pages')) as $file){
            if(!Str::endsWith($file, '.blade.php')){
                // Пропускаем не-шаблоны в этой папке — например {name}.fields.json
                // из Local JSON sync (см. adminUpdateTemplateFieldsAction).
                continue;
            }
            $parts = explode('/', $file);
            $templates[] = (object)[
                'name' => str_replace('.blade.php', '', end($parts)),
                'value' => str_replace([theme_relative_path('views/'), '.blade.php', '/'], ['', '', '.'], $file)
            ];
        }

        if($page->template == 'public.page'){
            $fields = null;
        }else{
            $fields = [];
            foreach(Config::get('app.locales_names') as $locale => $locale_name){
                $setting = $settings->get_setting('template_'.$page->template);
                $fields[$locale] = $this->updateTemplateData(!empty($setting) ? $settings->get_setting('template_'.$page->template)->fields : [], json_decode($page->localize($locale, 'body')));
            }

            foreach($fields as $lang => $lang_fields){
                $fields[$lang] = $pages->setFieldsImages($lang_fields);
            }
        }

        $all_pages = [
            (object)[
                'name' => trans('locale.Not selected'),
                'value' => null
            ]
        ];
        foreach($pages->where('id', '!=', $id)->get() as $p){
            $all_pages[] = (object)[
                'name' => $p->name,
                'value' => $p->id
            ];
        }

        return view('admin.pages.edit')
            ->with('templates', $templates)
            ->with('fields', $fields)
            ->with('pages', $all_pages)
            ->with('seo', $page->seo)
            ->with('page', $page)
            ->with('languages', Config::get('app.locales_names'))
            ->with('editors', Helper::localizationFields(['body', 'seo_description']));
    }

    /**
     * Список шаблонов блоков
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminTemplatesAction(){
        return view('admin.pages.templates.templates')
            ->with(['localization' => json_encode(['datatable' => trans('datatable')])]);
    }

    /**
     * Фильтр шаблонов блоков
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminTemplatesListAction(Request $request, Setting $settings){
        $files = [];
        $existing_paths = [];
        foreach(Storage::disk('local')->allFiles(theme_relative_path('views/public/layouts/pages')) as $file){
            if(!Str::endsWith($file, '.blade.php')){
                // Пропускаем не-шаблоны в этой папке — например {name}.fields.json
                // из Local JSON sync (см. adminUpdateTemplateFieldsAction).
                continue;
            }
            $parts = explode('/', $file);
            $name = str_replace('.blade.php', '', end($parts));
            $path = str_replace([theme_relative_path('views/'), '.blade.php', '/'], ['', '', '.'], $file);
            $existing_paths[] = $path;

            $actions = [];
            if($this->user->hasAccess(['pages.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/pages/template/'.$name)
                ];
                $actions[] = [
                    'type' => 'duplicate',
                    'name' => $name,
                    'link' => asset('admin/pages/template/duplicate/'.$name)
                ];
            }

            $files[] = [
                'path' => $path,
                'name' => $name,
                'category' => $this->templateCategory($settings, $path, $name, 'pages'),
                'actions' => $actions
            ];
        }

        // Шаблоны, на которые ссылаются страницы, но blade-файла для них уже
        // (или ещё) нет на диске — переименовали/удалили файл в обход админки.
        // Такие страницы молча падают при рендере на публичной части, поэтому
        // показываем их в списке отдельной "битой" строкой вместо тихой потери.
        foreach($this->missingTemplates(Page::class, $existing_paths, 'public.page') as $row){
            $name = str_replace('public.layouts.pages.', '', $row->template);
            $actions = [];
            if($this->user->hasAccess(['pages.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/pages/template/'.$name)
                ];
            }

            $files[] = [
                'path' => $row->template,
                'name' => $name,
                'missing' => true,
                'entries_count' => $row->entries_count,
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => count($files),
            'recordsFiltered' => count($files),
            'data' => $files
        ]);
    }

    /**
     * Обновление страницы
     *
     * @param $id
     * @param Request $request
     * @param Setting $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateAction($id, Request $request, Setting $settings){
        $rules = [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => 'required'
        ];
        $messages = [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '').'.required' => trans('locale.This field must be filled!')
        ];
        if($request->template == 'public.page'){
            $rules['body'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '')] = 'required';
            $messages['body'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '').'.required'] = trans('locale.This field must be filled!');
        }else{
            $fields = [];
            foreach(Config::get('app.locales_names') as $locale => $locale_name){
                $locale_settings = $settings->get_setting('template_'.$request->template);
                $fields[$locale] = !empty($locale_settings) ? $locale_settings->fields : [];
            }
            if(!empty($request->fields)){
                $fields = $this->fillInFields($fields, $request->fields);
                $data = [];
                if(count(Config::get('app.locales')) > 1){
                    foreach(Config::get('app.locales_names') as $locale => $locale_name){
                        $data['body_'.$locale] = json_encode($fields[$locale]);
                    }
                }else{
                    $data['body'] = json_encode($fields[$locale]);
                }
                $request->request->add($data);
            }else{
                $data = [];
                foreach(Config::get('app.locales_names') as $locale => $locale_name){
                    $data['body_'.$locale] = '';
                }
                $request->request->add($data);
            }
        }

        // Валидация значений полей по схеме шаблона (сейчас только required)
        // отдельно от $rules/$messages выше, т.к. поля адресуются по slug'у
        // из динамической схемы, а не по фиксированному имени input'а.
        // Проверяем только реально включённые локали (app.locales), а не все ключи
        // $fields — там, из-за рассинхрона app.locales/app.locales_names (см. config/app.php,
        // 'en' закомментирован в locales, но не в locales_names), может быть локаль,
        // которая структурно никогда не получает значение из запроса — required
        // на ней всегда бы ложно проваливал сохранение.
        $field_errors = [];
        if(!empty($fields)){
            foreach(Config::get('app.locales') as $locale){
                if(isset($fields[$locale])){
                    $field_errors = array_merge_recursive($field_errors, Fields::validateSubmission($fields[$locale]));
                }
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails() || !empty($field_errors)){
            $errors = array_merge_recursive($validator->errors()->toArray(), $field_errors);
            return response()->json(['result' => 'error', 'errors' => $errors, 'message' => trans('locale.validation_error')], 200);
        }

        $page = Page::find($id);

        $page_data = $page->fullData();

        $page->fill($request->only(['template', 'status', 'fields']));
        $page->parent_id = !empty($request->parent_id) ? $request->parent_id : null;
        $page->sort_order = !empty($request->sort_order) ? $request->sort_order : 0;
        $page->save();
        $page->saveLocalization($request);

        Action::updateEntity(Page::find($id), $page_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.changes_saved')], 200);
    }

    /**
     * Обновление SEO данных страницы
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSeoAction(Request $request, $id){
        $page = Page::find($id);

        if(empty($page)){
            return response()->json(['result' => 'error', 'message' => trans('locale.page.not_found')], 200);
        }

        $seo_data = $page->seo->fullData();
        $page->saveSeo($request);
        $page->load('seo');
        $page->slug = Str::slug(str_replace(['/', '_'], '', $page->seo->url));

        Action::updateEntity($page->seo, $seo_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.changes_saved')], 200);
    }

    /**
     * Обновление статуса страницы
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStatusAction(Request $request, $id){
        $page = Page::find($id);

        if(empty($page)){
            return response()->json(['result' => 'error', 'message' => trans('locale.page.not_found')], 200);
        }
        $page_data = $page->fullData();
        $page->status = (int)$request->status;
        $page->save();

        Action::updateEntity($page->find($id), $page_data);

        if($page->status)
            return response()->json(['result' => 'success', 'message' => trans('locale.page.enabled')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.page.disabled')], 200);
    }

    /**
     * Удаление страницы
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminDestroyAction($id){
        $page = Page::find($id);
        $name = $page->name;
        $page->delete();

        Action::deleteEntity($page);

        return response()->json(['result' => 'success', 'message' => trans('locale.page.deleted', ['name' => $name])], 200);
    }

    /**
     * Страница настройки шаблона
     *
     * @param $name
     * @param Setting $settings
     *
     * @return $this
     */
    public function adminTemplateAction($name, Setting $settings){
        $template = $settings->get_setting('template_public.layouts.pages.'.$name);
        $path = theme_relative_path("views/public/layouts/pages/$name.blade.php");

        if(empty($template)){
            // Local JSON fallback — если в settings пусто (свежее окружение без сида БД,
            // например только что развёрнутый стейджинг), но рядом с шаблоном в репозитории
            // есть {name}.fields.json, сохранённый при прошлом изменении схемы.
            $json_path = theme_relative_path("views/public/layouts/pages/$name.fields.json");
            if(Storage::disk('local')->exists($json_path)){
                $template = json_decode(Storage::disk('local')->get($json_path));
            }
        }

        if(empty($template)){
            $template = (object)[
                'path' => $path,
                'fields' => []
            ];
        }

        $template->name = $name;
        $template->html = Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : '';

        // Живое превью: показываем реальную страницу, использующую этот шаблон (если есть),
        // вместо того чтобы собирать фиктивный контекст рендера ($seo/$page/доп. переменные —
        // у разных шаблонов страниц они разные, например у 'home' их заметно больше, чем
        // просто $fields) — так превью всегда 100% совпадает с тем, что видит посетитель.
        $preview_page = Page::where('template', 'public.layouts.pages.'.$name)->whereHas('seo')->first();

        return view('admin.pages.templates.template')
            ->with('template', $template)
            ->with('preview_page', $preview_page)
            ->with('revisions', $this->templateRevisions('page_template_fields', 'page_template_html', 'public.layouts.pages.'.$name))
            ->with('treeview_data', $this->generateTreeviewData($template->fields));
    }

    public function generateTreeviewData($fields, $parent = null){
        $data = [];
        $icons = [
            'text' => 'bx bx-text',
            'textarea' => 'bx bx-menu',
            'wysiwyg' => 'bx bx-notepad',
            'oembed' => 'bx bx-image',
            'gallery' => 'bx bx-images',
            'select' => 'bx bx-list-check',
            'product' => 'bx bxs-shopping-bag',
            'relationship' => 'bx bx-file',
            'taxonomy' => 'bx bx-collection',
            'repeater' => 'bx bx-repeat',
            'group' => 'bx bx-folder',
            'number' => 'bx bx-hash',
            'email' => 'bx bx-envelope',
            'url' => 'bx bx-link',
            'date' => 'bx bx-calendar',
            'color' => 'bx bx-palette',
            'true_false' => 'bx bx-toggle-right',
        ];

        foreach($fields as $field){
            $field_data = [
                'text' => $field->name,
                'icon' => $icons[$field->type],
                'href' => rtrim($this->generateTemplate($field, empty($parent) ? '$fields' : '$' . $parent->slug), "\r\n")
            ];

            if(in_array($field->type, ['repeater', 'group'])){
                $field_data['nodes'] = $this->generateTreeviewData($field->fields, $field);
            }

            $data[] = $field_data;
        }

        return $data;
    }

    public function generateTemplate($field, $parent = '$fields'){
        $blade = '';

        if(in_array($field->type, ['text', 'textarea', 'wysiwyg', 'select', 'number', 'email', 'url', 'date', 'color'])){
            $blade .= "{!! field(" . $parent . ", '" . $field->slug . "') !!}\r\n";
        }elseif($field->type == 'true_false'){
            $blade .= "@if(field(" . $parent . ", '" . $field->slug . "'))\r\n\r\n@endif\r\n";
        }elseif($field->type == 'oembed'){
            $blade .= "{!! " . $parent . '[\'' . $field->slug . "']['image']->webp([1920, 1080], ['alt' => " .'$fields[\'' . $field->slug . "']['image']->alt" . "]) !!}\r\n";
        }elseif($field->type == 'gallery'){
            $blade .= '@foreach(' .$parent . '[\'' . $field->slug . '\'] as $' . $field->slug . "_image)\r\n";
            $blade .= '    {!! $' . $field->slug . "_image->webp([1920, 1080], ['alt' => \$" . $field->slug . "_image->alt]) !!}\r\n";
            $blade .= "@endforeach\r\n";
        }elseif($field->type == 'product'){
            $blade .= "{!! " . $parent . '[\'' . $field->slug . "']['product']->name !!}\r\n";
        }elseif($field->type == 'relationship'){
            $blade .= "{!! " . $parent . '[\'' . $field->slug . "']['page']->name !!}\r\n";
        }elseif($field->type == 'taxonomy'){
            $blade .= "{!! " . $parent . '[\'' . $field->slug . "']['category']->name !!}\r\n";
        }elseif(in_array($field->type, ['repeater', 'group'])){
            $blade .= '@foreach(' .$parent . '[\'' . $field->slug . '\'] as $' . $field->slug . ")\r\n";
            foreach($field->fields as $subfield){
                if(in_array($subfield->type, ['repeater', 'group'])){
                    $blade .= implode("\r\n    ", explode("\r\n", "    " . rtrim($this->generateTemplate($subfield, '$' . $field->slug), "\r\n"))) . "\r\n";
                }else{
                    $blade .= "    " . $this->generateTemplate($subfield, '$' . $field->slug);
                }
            }
            $blade .= "@endforeach\r\n";
        }

        return $blade;
    }

    /**
     * Обновление полей шаблона
     *
     * @param $name
     * @param Request $request
     * @param Setting $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateTemplateFieldsAction($name, Request $request, Setting $settings){
        $old_template = $settings->get_setting('template_public.layouts.pages.'.$name);

        $template = (object)[
            'path' => theme_relative_path("views/public/layouts/pages/$name.blade.php"),
            'name' => 'public.layouts.pages.'.$name,
            'category' => $request->category,
            'fields' => $this->refreshFieldsKeys($request->fields)
        ];

        $settings->update_setting('template_public.layouts.pages.'.$name, $template);

        // Local JSON sync: копия схемы рядом с blade-файлом шаблона, чтобы конфиг полей
        // ехал через git/деплой вместе с версткой, а не жил только в settings прод-базы.
        Storage::disk('local')->put(
            theme_relative_path("views/public/layouts/pages/$name.fields.json"),
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->recordTemplateRevision('page_template_fields', 'public.layouts.pages.'.$name, $old_template, $template);

        return response()->json(['result' => 'success', 'message' => trans('locale.changes_saved')], 200);
    }

    /**
     * Дублирование шаблона: копия HTML-файла и схемы полей под новым именем
     * ("Save as new" — быстрый старт нового шаблона на основе существующего).
     *
     * @param $name
     * @param Request $request
     * @param Setting $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDuplicateTemplateAction($name, Request $request, Setting $settings){
        $new_name = Str::slug($request->name, '-');

        if(empty($new_name)){
            return response()->json(['result' => 'error', 'message' => trans('locale.This field must be filled!')], 200);
        }

        $new_path = theme_relative_path("views/public/layouts/pages/$new_name.blade.php");

        if(Storage::disk('local')->exists($new_path)){
            return response()->json(['result' => 'error', 'message' => trans('locale.The value must be unique!')], 200);
        }

        $source_path = theme_relative_path("views/public/layouts/pages/$name.blade.php");
        Storage::disk('local')->put($new_path, Storage::disk('local')->exists($source_path) ? Storage::disk('local')->get($source_path) : '');

        $source_template = $settings->get_setting('template_public.layouts.pages.'.$name);
        if(empty($source_template)){
            $json_path = theme_relative_path("views/public/layouts/pages/$name.fields.json");
            if(Storage::disk('local')->exists($json_path)){
                $source_template = json_decode(Storage::disk('local')->get($json_path));
            }
        }

        $new_template = (object)[
            'path' => $new_path,
            'name' => 'public.layouts.pages.'.$new_name,
            'category' => !empty($source_template->category) ? $source_template->category : null,
            'fields' => !empty($source_template->fields) ? $source_template->fields : []
        ];

        $settings->update_setting('template_public.layouts.pages.'.$new_name, $new_template);
        Storage::disk('local')->put(
            theme_relative_path("views/public/layouts/pages/$new_name.fields.json"),
            json_encode($new_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.changes_saved'),
            'redirect' => asset('admin/pages/template/'.$new_name)
        ], 200);
    }

    /**
     * Сброс ключей в массивах сохраняемых данных
     *
     * @param $fields
     *
     * @return array
     */
    protected function refreshFieldsKeys($fields){
        $fields = array_values((array)$fields);

        foreach($fields as $key => $field){
            if(isset($field['fields'])){
                $fields[$key]['fields'] = $this->refreshFieldsKeys($field['fields']);
            }
        }

        return $fields;
    }

    /**
     * Обновление кода шаблона
     *
     * @param $name
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateTemplateFileAction($name, Request $request){
        $path = theme_relative_path("views/public/layouts/pages/$name.blade.php");
        $old_html = Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : null;

        Storage::disk('local')->put($path, $request->html);

        $this->recordTemplateRevision('page_template_html', 'public.layouts.pages.'.$name, $old_html, $request->html);

        return response()->json(['result' => 'success', 'message' => trans('locale.changes_saved')], 200);
    }

    /**
     * Восстановление предыдущей версии схемы полей или HTML шаблона из истории
     * изменений (см. RecordsTemplateRevisions).
     *
     * @param $name
     * @param Request $request
     * @param Setting $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminRestoreTemplateRevisionAction($name, Request $request, Setting $settings){
        $revision = Action::find($request->revision_id);

        if(empty($revision) || $revision->entity_id !== 'public.layouts.pages.'.$name){
            return response()->json(['result' => 'error', 'message' => trans('locale.Not found')], 200);
        }

        $restored = json_decode($revision->getRawOriginal('new_data'));

        if($revision->entity === 'page_template_fields'){
            $old_template = $settings->get_setting('template_public.layouts.pages.'.$name);
            $settings->update_setting('template_public.layouts.pages.'.$name, $restored);
            Storage::disk('local')->put(
                theme_relative_path("views/public/layouts/pages/$name.fields.json"),
                json_encode($restored, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->recordTemplateRevision('page_template_fields', 'public.layouts.pages.'.$name, $old_template, $restored);
        }elseif($revision->entity === 'page_template_html'){
            $path = theme_relative_path("views/public/layouts/pages/$name.blade.php");
            $old_html = Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : null;
            Storage::disk('local')->put($path, $restored);
            $this->recordTemplateRevision('page_template_html', 'public.layouts.pages.'.$name, $old_html, $restored);
        }else{
            return response()->json(['result' => 'error', 'message' => trans('locale.Not found')], 200);
        }

        return response()->json(['result' => 'success', 'message' => trans('locale.changes_saved')], 200);
    }

    /**
     * Добавление данных в настройки полей
     *
     * @param $fields
     * @param $request
     *
     * @return mixed
     */
    protected function fillInFields($fields, $request){
        foreach(Config::get('app.locales') as $lang){
            if(!isset($request[$lang])){
                $request[$lang] = [];
            }
        }
        $request = $this->mergeLangFields($request);
        foreach($fields as $lang => $lang_fields){
            foreach($lang_fields as $i => $field){
                if(in_array($field->type, ['repeater', 'group'])){
                    // ?? [] — на случай если для $lang вообще не пришло данных (например,
                    // рассинхрон config('app.locales') / config('app.locales_names'), из-за
                    // которого локаль есть в схеме полей, но не в присланном запросе)
                    $fields[$lang][$i]->data = $request[$lang][$field->slug] ?? [];
                }elseif($field->type == 'true_false'){
                    // Снятый чекбокс не попадает в $request вовсе (стандартное
                    // поведение HTML-формы) — без явного else поле бы тихо
                    // сохраняло прежнее значение вместо сброса в false.
                    $fields[$lang][$i]->value = isset($request[$lang][$field->slug]) ? 1 : 0;
                }else{
                    if(isset($request[$lang][$field->slug])){
                        $fields[$lang][$i]->value = $request[$lang][$field->slug];
                    }elseif(isset($request['all'][$field->slug])){
                        $fields[$lang][$i]->value = $request['all'][$field->slug];
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Формирование полного набора данных для каждого языка
     *
     * @param $fields
     *
     * @return mixed
     */
    protected function mergeLangFields($fields){
        if(isset($fields['all'])){
            foreach($fields['all'] as $key => $data){
                foreach($fields as $lng => $lang_fields){
                    if($lng != 'all'){
                        if(isset($lang_fields[$key])){
                            $fields[$lng][$key] = $this->mergeRepeaterFields($fields[$lng][$key], $data);
                        }else{
                            $fields[$lng][$key] = $data;
                        }
                    }
                }
            }
        }

        unset($fields['all']);

        return $fields;
    }

    /**
     * Формирование полного набора данных для повторителей
     *
     * @param $source
     * @param $merged
     *
     * @return mixed
     */
    protected function mergeRepeaterFields($source, $merged){
        foreach($merged as $i => $data){
            if(isset($source[$i])){
                $source[$i] = $this->mergeRepeaterFields($source[$i], $data);
            }else{
                $source[$i] = $data;
            }
        }

        return $source;
    }

    /**
     * Обновление настроек шаблона
     *
     * @param $template
     * @param $data
     *
     * @return mixed
     */
    protected function updateTemplateData($template, $data){
        $template = $this->setTemplateArrayKeys($template);
        $data = $this->setTemplateArrayKeys($data);

        foreach($template as $i => $field){
            if(isset($data[$i]) && ($field->type == $data[$i]->type || (in_array($field->type, ['text', 'textarea', 'wysiwyg']) && in_array($data[$i]->type, ['text', 'textarea', 'wysiwyg'])))){
                if(in_array($field->type, ['repeater', 'group'])){
                    $template[$i]->fields = $this->updateTemplateData($template[$i]->fields , $data[$i]->fields);
                    if(isset($data[$i]->data)){
                        $template[$i]->data = $data[$i]->data;
                    }else{
                        $template[$i]->data = [];
                    }
                }elseif(isset($data[$i]->value)){
                    $template[$i]->value = $data[$i]->value;
                }elseif(isset($field->default) && $field->default !== ''){
                    $template[$i]->value = $field->default;
                }
            }elseif(in_array($field->type, ['repeater', 'group'])){
                $template[$i]->data = [];
            }elseif(isset($field->default) && $field->default !== ''){
                // Поле только что добавлено в схему шаблона — данных под него
                // в уже сохранённой странице/блоке ещё нет вовсе
                $template[$i]->value = $field->default;
            }
        }

        return array_values($template);
    }

    protected function setTemplateArrayKeys($array){
        if(empty($array))
            return [];

        $new_array = [];
        foreach($array as $i => $data){
            $new_array[$data->slug] = $data;
        }

        return $new_array;
    }
}
