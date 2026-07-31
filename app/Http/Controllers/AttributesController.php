<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\Helper;
use App\Models\ProductAttributes;
use App\Models\AttributeValue;
use App\Models\Attribute;
use App\Models\Action;
use App\Models\User;
use App;

class AttributesController extends Controller
{
    /**
     * Список атрибутов
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction()
    {
        return view('admin.products.attributes.index')
            ->with([
                'breadcrumbs' => [['link' => '/admin', 'name' => trans('locale.Home')],['name' => trans('locale.Product attributes')]],
                'localization' => json_encode(['datatable' => trans('datatable'), 'js_messages' => trans('js_messages')])
            ]);
    }

    /**
     * Фильтр атрибутов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }

        $query = Attribute::select('attributes.*')
            ->with('values.localization');

        if($request->has('search.value')){
            $locale = App::getLocale();
            $text = $request->search['value'];
            $query->leftJoin('localization', function($leftJoin) use($text, $locale){
                $leftJoin->on('attributes.id', '=', 'localization.localizable_id')
                    ->where('localizable_type', 'Attributes')
                    ->where('field', 'name')
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

        $attributes = $query->get();

        $data = [];
        foreach($attributes as $attribute){
            $actions = [];
            if($user->hasAccess(['attributes.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/products/attributes/edit/'.$attribute->id)
                ];
            }
            if($user->hasAccess(['attributes.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $attribute->id,
                    'name' => $attribute->name
                ];
            }

            $values = [];
            $width = 0;
            foreach($attribute->values as $value){
                $width += mb_strlen($value->name) * 8.2 + 48;
                if($width > 680){
                    $values[] = '...';
                    break;
                }
                $values[] = $value->name;
            }

            $data[] = [
                'id' => $attribute->id,
                'name' => ['name' => $attribute->name],
                'values' => $values,
                'is_filter' => ['is_filter' => $attribute->is_filter, 'id' => $attribute->id],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Attribute::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Создание атрибута
     *
     * @param Request $request
     * @param Attribute $attributes
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminStoreAction(Request $request, Attribute $attributes): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ], [
            'name.required' => trans('locale.attributes.name_required_field')
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

	    $id = $attributes->insertGetId([
	        'slug' => Str::slug(str_replace(['-', '_', ' '], '', mb_strtolower(Helper::translit($request->name))))
        ]);
	    $attribute = $attributes->find($id);
        $request->merge([
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => $request->name
        ]);
	    $attribute->saveLocalization($request);

        $attribute->load('localization');
        Action::createEntity($attribute);

	    return response()->json(['result' => 'success', 'redirect' => '/admin/products/attributes/edit/'.$id]);
    }

    /**
     * Страница изменения атрибута
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminEditAction($id)
    {
        $attribute = Attribute::find($id);

        $types = [];
        foreach($attribute->getTypes() as $type => $name){
            $types[] = (object)['value' => $type, 'name' => $name];
        }

        return view('admin.products.attributes.edit')
            ->with('languages', Config::get('app.locales_names'))
            ->with('types', $types)
            ->with('attribute', $attribute)
            ->with('breadcrumbs', [
                ['link' => '/admin', 'name' => trans('locale.Home')],
                ['link' => '/admin/products/attributes', 'name' => trans('locale.Product attributes')],
                ['name' => $attribute->name]
            ]);
    }

    /**
     * Обновление атрибута
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateAction(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => 'required'
        ], [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '').'.required' => trans('locale.attributes.name_required_field')
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $attribute = Attribute::find($id);

        if(empty($attribute)){
            return response()->json(['result' => 'error', 'message' => trans('locale.attributes.attribute_not_found')], 200);
        }

        $old_settings = $attribute->toArray();
        $attribute_data = $attribute->getActionData();

        $attribute->fill($request->only($attribute->getFillable()));
        $attribute->visible = !empty($request->visible);
        $attribute->required_for_all = !empty($request->required_for_all);
        $attribute->is_variation_attribute = !empty($request->is_variation_attribute);
	    $attribute->saveLocalization($request);
        $attribute->save();

        Action::updateEntity($attribute, $attribute_data);

        if($this->getTypeGroup($old_settings['type']) != $this->getTypeGroup($attribute->type)
            || $old_settings['is_numeric_values'] != $attribute->is_numeric_values
            || $old_settings['unit'] != $attribute->unit){
            return response()->json([
                'result' => 'success',
                'message' => trans('locale.attributes.changes_saved'),
                'values' => view('admin.products.attributes.values')
                    ->with(['attribute' => $attribute])
                    ->with('languages', Config::get('app.locales_names'))
                    ->render()
            ], 200);
        }

        return response()->json(['result' => 'success', 'message' => trans('locale.attributes.changes_saved')], 200);
    }

    /**
     * Обновление значений атрибута
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateValuesAction(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $attribute = Attribute::find($id);
        if(empty($attribute)){
            return response()->json(['result' => 'error', 'message' => trans('locale.attributes.attribute_not_found')], 200);
        }

        $base_lang = Config::get('app.locale');
        $languages = Config::get('app.locales');
        if($attribute->is_numeric_values && !empty($languages)){
            $languages = [$base_lang => trans('locale.attributes.name')];
        }

        $rules = [
            'values.*.value' => 'distinct|filled'
        ];
        $messages = [
            'values.*.value.filled' => trans('locale.attributes.value_filled'),
        ];
        foreach($languages as $key => $name){
            $rules['name_'.$key] = 'distinct|filled';
            $messages['name_'.$key.'.distinct'] = trans('locale.attributes.value_distinct');
            $messages['name_'.$key.'.filled'] = trans('locale.attributes.value_filled');
        }

        $data = json_decode($request->data, true);
        foreach($data as $key => $val){
            $data[$key] = $key.'='.$val;
        }
        parse_str(implode('&', $data), $result);

        $validator = Validator::make($result, $rules, $messages);
        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->messages(), 'message' => trans('locale.attributes.form_validation_error')], 200);
        }

        if(!empty($result['values'])){
            $ids = [];
            foreach($result['values'] as $value){
                if(!empty($value['value_id'])){
                    $ids[] = $value['value_id'];
                }
            }
            $this->removeValues($attribute->values()->whereNotIn('id', $ids)->pluck('id')->toArray());

            foreach($result['values'] as $value){
                $attribute_value_id = $value['value_id'];
                if(empty($attribute_value_id) || empty($attribute_value = AttributeValue::find($attribute_value_id))){
                    $attribute_value = new AttributeValue();
                    $attribute_value->attribute_id = $attribute->id;
                }
                $attribute_value->value = Str::slug(str_replace(['-', '_', ' '], '', !empty($value['value']) ? $value['value'] : mb_strtolower(Helper::translit($value['name'.(count(Config::get('app.locales')) > 1 ? '_'.$base_lang : '')]))));
                $attribute_value->file_id = empty($value['file_id']) ? null : $value['file_id'];
                $attribute_value->save();
                $new_request = new Request();
                $names = [];
                if(count(Config::get('app.locales')) > 1){
                    foreach($languages as $key => $name){
                        $names['name_'.$key] = $value['name_'.$key];
                    }
                }else{
                    $names['name'] = $value['name'];
                }
                $new_request->merge($names);
                $attribute_value->saveLocalization($new_request);
            }
        }else{
            $this->removeValues($attribute->values->pluck('id')->toArray());
        }

        return response()->json(['result' => 'success', 'message' => trans('locale.attributes.changes_saved')], 200);
    }

    /**
     * Удаление значений атрибутов
     *
     * @param $ids
     */
    private function removeValues($ids){
        ProductAttributes::whereIn('attribute_value_id', $ids)->delete();
        AttributeValue::whereIn('id', $ids)->delete();
    }

    /**
     * Определение группы по типу атрибута
     *
     * @param $type
     * @return string|null
     */
    public function getTypeGroup($type){
        if(in_array($type, ['multiple_checkboxes', 'multiple_select', 'single_radio', 'single_select'])){
            return 'text';
        }else if(in_array($type, ['multiple_color_checkboxes', 'single_color_radio'])){
            return 'image';
        }else if(in_array($type, ['range', 'range_slider'])){
            return 'number';
        }else if($type === 'yes_no'){
            return 'boolean';
        }

        return null;
    }

    /**
     * Переключение режима фильтра
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateFilterStatusAction(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $attribute = Attribute::find($id);

        if(empty($attribute)){
            return response()->json(['result' => 'error', 'message' => trans('locale.attributes.attribute_not_found')], 200);
        }
        $attribute_data = $attribute->getActionData();
        $attribute->is_filter = (int)$request->status;
        $attribute->save();

        Action::updateEntity($attribute, $attribute_data);

        if($attribute->is_filter)
            return response()->json(['result' => 'success', 'message' => trans('locale.attributes.filter_activated')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.attributes.filter_deactivated')], 200);
    }

    /**
     * Удаление атрибута
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDeleteAction($id){
        $attribute = Attribute::find($id);

        if(empty($attribute)){
            return response()->json(['result' => 'error', 'message' => trans('locale.attributes.attribute_not_found')], 200);
        }

        // Удаление атрибута у товара
	    ProductAttributes::where('attribute_id', $id)->delete();
        $name = $attribute->name;

        Action::deleteEntity($attribute);

        // Удаление значений атрибута
	    $attribute->values()->delete();
        // Открепление категорий
	    $attribute->categories()->detach();
        // Удаление атрибута
        $attribute->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.attributes.attribute_deleted', ['name' => $name])], 200);
    }

    /**
     * Варианты атрибутов в json формате
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminAttributeValuesApi($id){
        $attribute = Attribute::find($id);

        if(empty($attribute)){
            return response()->json(['result' => 'error', 'message' => trans('locale.attributes.attribute_not_found')], 200);
        }

        $values = [];
        foreach($attribute->values()->with('localization')->get() as $value){
            $values[] = [
                'id' => $value->id,
                'external_id' => $value->external_id,
                'attribute_id' => $value->attribute_id,
                'value' => $value->value,
                'file_id' => $value->file_id,
                'name' => $value->name,
            ];
        }

        return response()->json([
            'result' => 'success',
            'values' => $values
        ]);
    }
}
