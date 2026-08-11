<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Helpers\Fields;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Action;
use App\Models\Block;
use App\Models\File;
use App;

class BlocksController extends Controller
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
            'name.required' => trans('validation.required', ['attribute' => trans('Name')]),
            'name.unique' => trans('validation.unique', ['attribute' => trans('Name')]),
            'body.required' => trans('validation.required', ['attribute' => trans('Content')])
        ];
    }

    /**
     * Список блоков
     *
     * @return \Illuminate\Http\Response
     */
    public function adminIndexAction(){
        return view('admin.blocks.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable')])]);
    }

    /**
     * Фильтр блоков
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $query = Block::select('blocks.*');

        if($request->has('search.value')){
            $locale = App::getLocale();
            $text = $request->search['value'];
            $query->leftJoin('localization', function($query) use($text, $locale){
                $query->on('blocks.id', '=', 'localization.localizable_id')
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

        $blocks = $query->get();

        $data = [];
        foreach($blocks as $block){
            $actions = [];
            if($this->user->hasAccess(['blocks.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/blocks/edit/'.$block->id)
                ];
            }
            if($this->user->hasAccess(['blocks.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $block->id,
                    'name' => $block->name
                ];
            }

            $data[] = [
                'id' => $block->id,
                'name' => $block->name,
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Block::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Создание блока
     *
     * @param Request $request
     * @param Block $blocks
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStoreAction(Request $request, Block $blocks){
        $validator = Validator::make($request->all(), ['name' => 'required'], ['name.required' => trans('locale.This field must be filled!')]);

        if($validator->fails()){
            return response()->json($validator);
        }

        $rd = new Request();
        $rd->merge(['name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => $request->name]);

        $id = $blocks->insertGetId(['template' => 'public.layouts.blocks.faq']);
        $block = $blocks->find($id);
        $block->saveLocalization($rd);

        Action::createEntity($block);

        return response()->json(['result' => 'success', 'redirect' => '/admin/blocks/edit/'.$id]);
    }

    /**
     * Страница обновления блока
     *
     * @param $id
     * @param Block $blocks
     * @param Setting $settings
     * @return mixed
     */
    public function adminEditAction($id, Block $blocks, Setting $settings){
        $block = $blocks->find($id);
        $templates = [];
        foreach(Storage::disk('local')->allFiles(theme_relative_path('views/public/layouts/blocks')) as $file){
            if(!Str::endsWith($file, '.blade.php')){
                // Пропускаем не-шаблоны в этой папке — например {name}.fields.json
                // из Local JSON sync (см. adminUpdateTemplateFieldsAction).
                continue;
            }
            $parts = explode('/', $file);
            if(end($parts) !== 'main.blade.php'){
                $templates[] = (object)[
                    'name' => str_replace('.blade.php', '', end($parts)),
                    'value' => str_replace([theme_relative_path('views/'), '.blade.php', '/'], ['', '', '.'], $file)
                ];
            }
        }

        if($block->template == 'public.layouts.blocks.main'){
            $fields = null;
        }else{
            $fields = [];
            foreach(Config::get('app.locales_names') as $locale => $locale_name){
                $setting = $settings->get_setting('template_'.$block->template);
                $fields[$locale] = $this->updateTemplateData(!empty($setting) ? $setting->fields : [], json_decode($block->localize($locale, 'body')));
            }

            foreach($fields as $lang => $lang_fields){
                $fields[$lang] = $block->setFieldsImages($lang_fields);
            }
        }

        return view('admin.blocks.edit')
            ->with('templates', $templates)
            ->with('fields', $fields)
            ->with('block', $block)
            ->with('languages', Config::get('app.locales_names'))
            ->with('main_lang', Config::get('app.locale'))
            ->with('editors', Helper::localizationFields(['body']));
    }

    /**
     * Обновление блока
     *
     * @param $id
     * @param Request $request
     * @param Setting $settings
     *
     * @return $this
     */
    public function adminUpdateAction($id, Request $request, Setting $settings){
        $rules = [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => 'required'
        ];
        $messages = [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '').'.required' => trans('locale.This field must be filled!')
        ];

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

        // Валидация значений полей по схеме шаблона (сейчас только required)
        // отдельно от $rules/$messages выше, т.к. поля адресуются по slug'у
        // из динамической схемы, а не по фиксированному имени input'а.
        // Проверяем только реально включённые локали (app.locales), а не все ключи
        // $fields — там, из-за рассинхрона app.locales/app.locales_names (см. config/app.php,
        // 'en' закомментирован в locales, но не в locales_names), может быть локаль,
        // которая структурно никогда не получает значение из запроса — required
        // на ней всегда бы ложно проваливал сохранение.
        $field_errors = [];
        foreach(Config::get('app.locales') as $locale){
            if(isset($fields[$locale])){
                $field_errors = array_merge_recursive($field_errors, Fields::validateSubmission($fields[$locale]));
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails() || !empty($field_errors)){
            $errors = array_merge_recursive($validator->errors()->toArray(), $field_errors);
            return response()->json(['result' => 'error', 'errors' => $errors, 'message' => trans('locale.validation_error')], 200);
        }

        $block = Block::find($id);

        $block_data = $block->fullData();

        $block->fill($request->only(['template', 'fields']));
        $block->save();
        $block->saveLocalization($request);

        Action::updateEntity(Block::find($id), $block_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
    }

    /**
     * Удаление блока
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminDestroyAction($id){
        $block = Block::find($id);
        $name = $block->name;
        $block->delete();

        Action::deleteEntity($block);

        return response()->json(['result' => 'success', 'message' => trans('locale.messages.block_deleted', ['name' => $name])], 200);
    }

    /**
     * Список шаблонов блоков
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminTemplatesAction(){
        return view('admin.blocks.templates.templates')
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
        foreach(Storage::disk('local')->allFiles(theme_relative_path('views/public/layouts/blocks')) as $file){
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
            if($this->user->hasAccess(['blocks.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/blocks/template/'.$name)
                ];
                $actions[] = [
                    'type' => 'duplicate',
                    'name' => $name,
                    'link' => asset('admin/blocks/template/duplicate/'.$name)
                ];
            }

            $files[] = [
                'path' => $path,
                'name' => $name,
                'category' => $this->templateCategory($settings, $path, $name, 'blocks'),
                'actions' => $actions
            ];
        }

        // Шаблоны, на которые ссылаются блоки, но blade-файла для них уже
        // (или ещё) нет на диске — переименовали/удалили файл в обход админки.
        // Такие блоки молча падают при рендере (BlockShortcode ловит Exception
        // и просто ничего не выводит), поэтому показываем их отдельной "битой"
        // строкой вместо тихой потери контента.
        foreach($this->missingTemplates(Block::class, $existing_paths, 'public.layouts.blocks.main') as $row){
            $name = str_replace('public.layouts.blocks.', '', $row->template);
            $actions = [];
            if($this->user->hasAccess(['blocks.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/blocks/template/'.$name)
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
     * Страница настройки шаблона
     *
     * @param $name
     * @param Setting $settings
     *
     * @return $this
     */
    public function adminTemplateAction($name, Setting $settings){
        $template = $settings->get_setting('template_public.layouts.blocks.'.$name);
        $path = theme_relative_path("views/public/layouts/blocks/$name.blade.php");

        if(empty($template)){
            // Local JSON fallback — если в settings пусто (свежее окружение без сида БД,
            // например только что развёрнутый стейджинг), но рядом с шаблоном в репозитории
            // есть {name}.fields.json, сохранённый при прошлом изменении схемы.
            $json_path = theme_relative_path("views/public/layouts/blocks/$name.fields.json");
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

        // Живое превью: показываем реальный блок, использующий этот шаблон (если есть),
        // вместо того чтобы собирать фиктивные $fields — так превью всегда совпадает с тем,
        // что реально выводит [block id="X"] на публичной части.
        $preview_block = Block::where('template', 'public.layouts.blocks.'.$name)->first();

        return view('admin.blocks.templates.template')
            ->with('template', $template)
            ->with('preview_block', $preview_block)
            ->with('revisions', $this->templateRevisions('block_template_fields', 'block_template_html', 'public.layouts.blocks.'.$name))
            ->with('treeview_data', $this->generateTreeviewData($template->fields));
    }

    /**
     * Живое превью шаблона блока в минимальной HTML-обёртке с основным CSS сайта —
     * рендерит реальный блок (см. adminTemplateAction) той же логикой, что и
     * BlockShortcode на публичной части.
     *
     * @param $name
     * @return \Illuminate\Http\Response
     */
    public function adminTemplatePreviewAction($name){
        $block = Block::where('template', 'public.layouts.blocks.'.$name)->first();

        if(empty($block)){
            return response(
                '<html><body style="font-family:sans-serif;padding:20px;">'.
                e(trans('locale.This template is not linked to any page or block yet — create one to see a live preview.')).
                '</body></html>'
            );
        }

        try{
            $fields = [];
            $body = $block->body;

            if(!empty($body)){
                $d = $block->setFieldsCategories($block->setFieldsPages($block->setFieldsProducts($block->setFieldsImages(json_decode($block->localize(app()->getLocale(), 'body'))))));
                foreach($d as $field){
                    if(in_array($field->type, ['repeater', 'group'])){
                        $fields[$field->slug] = $field->data;
                    }else{
                        $fields[$field->slug] = isset($field->value) ? $field->value : '';
                    }
                }
            }

            $html = view($block->template, ['fields' => $fields])->render();
        }catch(\Exception $e){
            $html = '<p style="font-family:sans-serif;color:#c00;padding:20px;">'.e($e->getMessage()).'</p>';
        }

        return response('<html><head><link rel="stylesheet" href="'.theme_mix('css/app.css').'"></head><body>'.$html.'</body></html>');
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
        $old_template = $settings->get_setting('template_public.layouts.blocks.'.$name);

        $template = (object)[
            'path' => theme_relative_path("views/public/layouts/blocks/$name.blade.php"),
            'name' => 'public.layouts.blocks.'.$name,
            'category' => $request->category,
            'fields' => $this->refreshFieldsKeys($request->fields)
        ];

        $settings->update_setting('template_public.layouts.blocks.'.$name, $template);

        // Local JSON sync: копия схемы рядом с blade-файлом шаблона, чтобы конфиг полей
        // ехал через git/деплой вместе с версткой, а не жил только в settings прод-базы.
        Storage::disk('local')->put(
            theme_relative_path("views/public/layouts/blocks/$name.fields.json"),
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->recordTemplateRevision('block_template_fields', 'public.layouts.blocks.'.$name, $old_template, $template);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
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

        $new_path = theme_relative_path("views/public/layouts/blocks/$new_name.blade.php");

        if(Storage::disk('local')->exists($new_path)){
            return response()->json(['result' => 'error', 'message' => trans('locale.The value must be unique!')], 200);
        }

        $source_path = theme_relative_path("views/public/layouts/blocks/$name.blade.php");
        Storage::disk('local')->put($new_path, Storage::disk('local')->exists($source_path) ? Storage::disk('local')->get($source_path) : '');

        $source_template = $settings->get_setting('template_public.layouts.blocks.'.$name);
        if(empty($source_template)){
            $json_path = theme_relative_path("views/public/layouts/blocks/$name.fields.json");
            if(Storage::disk('local')->exists($json_path)){
                $source_template = json_decode(Storage::disk('local')->get($json_path));
            }
        }

        $new_template = (object)[
            'path' => $new_path,
            'name' => 'public.layouts.blocks.'.$new_name,
            'category' => !empty($source_template->category) ? $source_template->category : null,
            'fields' => !empty($source_template->fields) ? $source_template->fields : []
        ];

        $settings->update_setting('template_public.layouts.blocks.'.$new_name, $new_template);
        Storage::disk('local')->put(
            theme_relative_path("views/public/layouts/blocks/$new_name.fields.json"),
            json_encode($new_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.Changes saved'),
            'redirect' => asset('admin/blocks/template/'.$new_name)
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
        $path = theme_relative_path("views/public/layouts/blocks/$name.blade.php");
        $old_html = Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : null;

        Storage::disk('local')->put($path, $request->html);

        $this->recordTemplateRevision('block_template_html', 'public.layouts.blocks.'.$name, $old_html, $request->html);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
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

        if(empty($revision) || $revision->entity_id !== 'public.layouts.blocks.'.$name){
            return response()->json(['result' => 'error', 'message' => trans('locale.Not found')], 200);
        }

        $restored = json_decode($revision->getRawOriginal('new_data'));

        if($revision->entity === 'block_template_fields'){
            $old_template = $settings->get_setting('template_public.layouts.blocks.'.$name);
            $settings->update_setting('template_public.layouts.blocks.'.$name, $restored);
            Storage::disk('local')->put(
                theme_relative_path("views/public/layouts/blocks/$name.fields.json"),
                json_encode($restored, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $this->recordTemplateRevision('block_template_fields', 'public.layouts.blocks.'.$name, $old_template, $restored);
        }elseif($revision->entity === 'block_template_html'){
            $path = theme_relative_path("views/public/layouts/blocks/$name.blade.php");
            $old_html = Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : null;
            Storage::disk('local')->put($path, $restored);
            $this->recordTemplateRevision('block_template_html', 'public.layouts.blocks.'.$name, $old_html, $restored);
        }else{
            return response()->json(['result' => 'error', 'message' => trans('locale.Not found')], 200);
        }

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
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
                // в уже сохранённом блоке ещё нет вовсе
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
