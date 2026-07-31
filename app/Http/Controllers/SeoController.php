<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Setting;
use App\Models\Action;
use App\Models\File;
use App\Models\Seo;


class SeoController extends Controller
{
	private $user;
	private $settings;

    private $rules = [
        'url' => 'required|unique:seo',
    ];

    private $messages = [];

	function __construct(Setting $settings){
		$this->user = Sentinel::check();
		$this->settings = $settings;

        $this->messages = [
            'name.required' => trans('locale.validation.required'),
            'meta_title.required' => trans('locale.validation.required'),
            'url.required' => trans('locale.validation.required'),
            'url.unique' => trans('locale.validation.unique')
        ];
	}

    /**
     * Список SEO записей
     *
     * @return \Illuminate\Http\Response
     */
    public function adminIndexAction(){
        if(!$this->user->hasAccess(['seo.read'])){
            return response(view('errors.403'),403);
        }

        return view('admin.promotion.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable')])]);
    }

    /**
     * Фильтр SEO записей
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request): \Illuminate\Http\JsonResponse
    {
        if(!$this->user->hasAccess(['seo.read'])){
            return response()->json(['result' => 'error', 'message' => trans('auth.unauthorized')]);
        }

        $query = Seo::select('seo.*');

        if($request->has('search.value')){
            $text = $request->search['value'];
            $query->where('url', 'like', '%'.$text.'%');
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $query->orderBy($request->columns[$request->order[0]['column']]['data'], $request->order[0]['dir']);

        $items = $query->get();

        $data = [];
        foreach($items as $item){
            $actions = [];
            if($this->user->hasAccess(['seo.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/promotion/edit/'.$item->id)
                ];
            }
            if($this->user->hasAccess(['seo.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $item->id,
                    'name' => $item->url
                ];
            }

            $data[] = [
                'id' => $item->id,
                'url' => $item->url,
                'name' => $item->name,
                'type' => $item->type_name,
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Seo::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Страница создания SEO записи
     *
     * @param Seo $seo
     * @return mixed
     */
    public function adminCreateAction(Seo $seo){
        if(!$this->user->hasAccess(['seo.create'])){
            return response(view('errors.403'),403);
        }

        return view('admin.promotion.create')
            ->with('types', $seo->types_select_data)
            ->with('languages', Config::get('app.locales_names'))
            ->with('editors', Helper::localizationFields(['body', 'seo_description']));
    }

    /**
     * Создание новой SEO записи
     *
     * @param Request $request
     * @param Seo $seo
     * @return $this
     */
    public function adminStoreAction(Request $request, Seo $seo){
        if(!$this->user->hasAccess(['seo.create'])){
            return response()->json(['result' => 'error', 'message' => trans('auth.unauthorized')]);
        }

        $validator = Validator::make($request->all(), $this->rules, $this->messages);

        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $isset = Seo::where($request->only(['seotable_id', 'seotable_type']))->first();
        if(!empty($isset)){
            return response()->json([
                'result' => 'error',
                'message' => trans('seo.entry_exists', [
                    'link' => '/admin/promotion/edit/'.$isset->id
                ])
            ]);
        }

        $seo->fill($request->except('_token'));
        $seo->seotable_id = !empty($request->seotable_id) ? $request->seotable_id : 0;
        $seo->action = !empty($request->action) ? $request->action : 'showAction';
        $seo->save();
        $seo->saveLocalization($request);

        Action::createEntity($seo);

        return response()->json(['result' => 'success', 'redirect' => '/admin/promotion/edit/'.$seo->id]);
    }

    /**
     * Страница редактирования SEO записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminEditAction($id){
        if(!$this->user->hasAccess(['seo.read'])){
            return response(view('errors.403'),403);
        }

        $seo = Seo::find($id);

        if(empty($seo)){
            abort(404);
        }

        return view('admin.promotion.edit')
            ->with('seo', $seo)
	        ->with('languages', Config::get('app.locales_names'))
	        ->with('editors', Helper::localizationFields(['seo_description']));
    }

	/**
     * Обновление SEO записи
     *
	 * @param Request $request
     * @param $id
	 *
	 * @return $this
	 */
    public function adminUpdateAction(Request $request, $id){
        if(!$this->user->hasAccess(['seo.write'])){
            return response()->json(['result' => 'error', 'message' => trans('seo.no_access_update')]);
        }

        $seo = Seo::find($id);

        if(empty($seo)){
            return response()->json(['result' => 'error', 'message' => trans('seo.not_found')], 200);
        }

        $rules = $this->rules;
        $rules['url'] = 'required|unique:seo,url,'.$id;

        $validator = Validator::make($request->all(), $rules, $this->messages);
        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $seo_data = $seo->fullData();
        $seo->fill($request->only(['canonical', 'robots', 'url']));
        $seo->save();
        $seo->saveLocalization($request);

        Action::updateEntity($seo->find($id), $seo_data);

        return response()->json(['result' => 'success', 'message' => trans('seo.updated')], 200);
    }

    /**
     * Удаление SEO записи
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminDeleteAction($id)
    {
        if (!$this->user->hasAccess(['seo.delete'])) {
            return response()->json(['result' => 'error', 'message' => trans('seo.no_access_delete')]);
        }

        $seo = Seo::find($id);

        if (empty($seo)) {
            return response()->json(['result' => 'error', 'message' => trans('seo.not_found')], 200);
        }

        Action::deleteEntity($seo);

        $name = $seo->name;
        if (empty($name)) {
            $name = $seo->url;
        }

        // Удаление переводов
        $seo->localization()->delete();
        // Удаление SEO записи
        $seo->forceDelete();

        return response()->json(['result' => 'success', 'message' => trans('seo.deleted', ['name' => $name])], 200);
    }

    public function seoSettingsAction(Setting $settings)
    {
        if (!$this->user->hasAccess(['seo.read'])) {
            return response(view('errors.403'), 403);
        }

        $ld_types = [];
        foreach (trans('locale.ld_types') as $value => $name) {
            $ld_types[] = (object)['name' => $name, 'id' => $value];
        }

        $ld_payments = [];
        foreach (trans('locale.ld_payments') as $id => $name) {
            $ld_payments[] = (object)['id' => $id, 'name' => $name];
        }

        return view('admin.promotion.settings')
            ->with('user', $this->user)
            ->with('settings', $settings->get_all())
            ->with('ld_types', $ld_types)
            ->with('ld_payments', $ld_payments)
            ->with('image', empty($settings->get_setting('ld_image')) ? File::find(1) : File::find($settings->get_setting('ld_image')))
            ->with('locales_names', Config::get('app.locales_names'))
            ->with('main_lang', Config::get('app.locale'));
    }

    public function adminUpdateGoogleSettingsAction(Request $request, Setting $settings)
    {
        $rules = [
            'google' => 'required|string',
            'google_noscript' => 'required|string'
        ];

        $messages = [
            'google.required' => trans('locale.validation.required', ['attribute' => 'GTM']),
            'google_noscript.required' => trans('locale.validation.required', ['attribute' => 'GTM noscript']),
            'google.string' => trans('locale.validation.string', ['attribute' => 'GTM']),
            'google_noscript.string' => trans('locale.validation.string', ['attribute' => 'GTM noscript'])
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $settings->update_settings($request->only(['google', 'google_noscript', 'ega', 'ads', 'google_conversion_id']), true);

        return response()->json(['result' => 'success', 'message' => trans('seo.google_settings_saved')], 200);
    }

    public function adminUpdateFacebookSettingsAction(Request $request, Setting $settings)
    {
        $settings->update_settings($request->only(['fb_pixel']), true);
        return response()->json(['result' => 'success', 'message' => trans('locale.seo.facebook_settings_saved')], 200);
    }

    public function adminUpdateMicrodataSettingsAction(Request $request, Setting $settings)
    {
        $rules = [
            'ld_type' => 'required',
            'ld_name' => 'required',
            'ld_description' => 'required',
        ];

        $messages = [
            'ld_type.required' => trans('locale.validation.required', ['attribute' => trans('seo.ld_type')]),
            'ld_name.required' => trans('locale.validation.required', ['attribute' => trans('seo.ld_name')]),
            'ld_description.required' => trans('locale.validation.required', ['attribute' => trans('seo.ld_description')]),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $settings->update_settings($request->only([
            'ld_type', 'ld_name', 'ld_description', 'ld_image', 'ld_region', 'ld_city',
            'ld_street', 'ld_postcode', 'ld_phone', 'ld_payments', 'ld_opening_hours',
            'ld_latitude', 'ld_longitude', 'social'
        ]), true);

        return response()->json(['result' => 'success', 'message' => trans('locale.seo.microdata_settings_saved')], 200);
    }

    public function adminUpdateTemplateSettingsAction(Request $request, Setting $settings)
    {
        // Get all active languages from config
        $locales = Config::get('app.locales', []);
        $templateFields = [];

        // Generate field names for all active languages
        foreach ($locales as $lang) {
            $templateFields[] = 'products_meta_title_' . $lang;
            $templateFields[] = 'products_meta_description_' . $lang;
            $templateFields[] = 'products_meta_keywords_' . $lang;
            $templateFields[] = 'categories_meta_title_' . $lang;
            $templateFields[] = 'categories_meta_description_' . $lang;
            $templateFields[] = 'categories_meta_keywords_' . $lang;
            $templateFields[] = 'filters_meta_title_' . $lang;
            $templateFields[] = 'filters_meta_description_' . $lang;
            $templateFields[] = 'filters_meta_keywords_' . $lang;
            $templateFields[] = 'filters_meta_h1_' . $lang;
        }

        // Basic validation rules
        $rules = [];
        $messages = [];

        foreach ($templateFields as $field) {
            $rules[$field] = 'nullable|string|max:500';
            $messages[$field . '.string'] = 'The ' . str_replace('_', ' ', $field) . ' must be a string.';
            $messages[$field . '.max'] = 'The ' . str_replace('_', ' ', $field) . ' may not be greater than 500 characters.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        // Get all request data that matches template fields
        $templateData = [];
        foreach ($templateFields as $field) {
            if ($request->has($field)) {
                $templateData[$field] = $request->get($field);
            }
        }

        // Save template settings
        $settings->update_settings($templateData, true);

        return response()->json(['result' => 'success', 'message' => 'Template settings saved successfully'], 200);
    }
}
