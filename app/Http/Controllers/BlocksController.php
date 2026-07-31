<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Action;
use App\Models\Block;
use App\Models\File;
use App;

class BlocksController extends Controller
{
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
        foreach(Storage::disk('local')->allFiles('/resources/views/public/layouts/blocks') as $file){
            $parts = explode('/', $file);
            if(end($parts) !== 'main.blade.php'){
                $templates[] = (object)[
                    'name' => str_replace('.blade.php', '', end($parts)),
                    'value' => str_replace(['resources/views/', '.blade.php', '/'], ['', '', '.'], $file)
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

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->messages(), 'message' => trans('locale.validation_error')], 200);
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
    public function adminTemplatesListAction(Request $request){
        $files = [];
        foreach(Storage::disk('local')->allFiles('/resources/views/public/layouts/blocks') as $file){
            $parts = explode('/', $file);
            $name = str_replace('.blade.php', '', end($parts));
            $actions = [];
            if($this->user->hasAccess(['blocks.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/blocks/template/'.$name)
                ];
            }

            $files[] = [
                'path' => str_replace(['resources/views/', '.blade.php', '/'], ['', '', '.'], $file),
                'name' => $name,
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
        $path = "resources/views/public/layouts/blocks/$name.blade.php";

        if(empty($template)){
            $template = (object)[
                'path' => $path,
                'fields' => []
            ];
        }

        $template->name = $name;
        $template->html = Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : '';

        return view('admin.blocks.templates.template')
            ->with('template', $template)
            ->with('treeview_data', $this->generateTreeviewData($template->fields));
    }

    public function generateTreeviewData($fields, $parent = null){
        $data = [];
        $icons = [
            'text' => 'bx bx-text',
            'textarea' => 'bx bx-menu',
            'wysiwyg' => 'bx bx-notepad',
            'oembed' => 'bx bx-image',
            'select' => 'bx bx-list-check',
            'product' => 'bx bxs-shopping-bag',
            'repeater' => 'bx bx-repeat',
        ];

        foreach($fields as $field){
            $field_data = [
                'text' => $field->name,
                'icon' => $icons[$field->type],
                'href' => rtrim($this->generateTemplate($field, empty($parent) ? '$fields' : '$' . $parent->slug), "\r\n")
            ];

            if($field->type == 'repeater'){
                $field_data['nodes'] = $this->generateTreeviewData($field->fields, $field);
            }

            $data[] = $field_data;
        }

        return $data;
    }

    public function generateTemplate($field, $parent = '$fields'){
        $blade = '';

        if(in_array($field->type, ['text', 'textarea', 'wysiwyg', 'select'])){
            $blade .= "{!! " . $parent . '[\'' . $field->slug . "'] !!}\r\n";
        }elseif($field->type == 'oembed'){
            $blade .= "{!! " . $parent . '[\'' . $field->slug . "']['image']->webp([1920, 1080], ['alt' => " .'$fields[\'' . $field->slug . "']['image']->alt" . "]) !!}\r\n";
        }elseif($field->type == 'repeater'){
            $blade .= '@foreach(' .$parent . '[\'' . $field->slug . '\'] as $' . $field->slug . ")\r\n";
                foreach($field->fields as $subfield){
                    if($subfield->type == 'repeater'){
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
        $template = (object)[
            'path' => "resources/views/public/layouts/blocks/$name.blade.php",
            'name' => 'public.layouts.blocks.'.$name,
            'fields' => $this->refreshFieldsKeys($request->fields)
        ];

        $settings->update_setting('template_public.layouts.blocks.'.$name, $template);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
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
        $path = "resources/views/public/layouts/blocks/$name.blade.php";

        Storage::disk('local')->put($path, $request->html);

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
                if($field->type == 'repeater'){
                    $fields[$lang][$i]->data = $request[$lang][$field->slug];
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
                if($field->type == 'repeater'){
                    $template[$i]->fields = $this->updateTemplateData($template[$i]->fields , $data[$i]->fields);
                    if(isset($data[$i]->data)){
                        $template[$i]->data = $data[$i]->data;
                    }else{
                        $template[$i]->data = [];
                    }
                }elseif(isset($data[$i]->value)){
                    $template[$i]->value = $data[$i]->value;
                }
            }elseif($field->type == 'repeater'){
                $template[$i]->data = [];
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
