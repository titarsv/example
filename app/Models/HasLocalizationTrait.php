<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Support\Str;

trait HasLocalizationTrait
{
    public function localization(){
        return $this->morphMany('App\Models\Localization', 'localizable');
    }

    public function seotable(){
        return $this->morphTo();
    }

    public function saveLocalization($request){
        $localization = new Localization();
        $localization->saveLocalization($request, $this, Helper::localizationFields($this->localized_fields));
    }

    public function localize($language, $field){
        $localization = $this->localization->first(function ($value, $key) use ($language, $field){
            return $value->language == $language && $value->field == $field;
        });
        if(empty($localization) && !isset($this->relations['localization']))
            $localization = $this->localization()->where(['language' => $language, 'field' => $field])->first();

        if(empty($localization)){
            $method = Str::camel('get_'.$field.'_template');
            if(method_exists($this, $method) && is_callable([$this, $method])){
                return $this->{$method}();
            }else{
                return '';
            }
        }else{
            if(!empty($this->fields_with_pictures) && in_array($field, $this->fields_with_pictures)){
                if(\Request::segment( 1 ) == 'admin'){
                    $value = $localization->value;
                    $value = preg_replace('/<source.*?data-src="(.*?)" type="image\/(.*?)" \/>/s', '<source srcset="$1" type="image/$2" />', $value);
                    return $value;
                }

                return str_replace('editor-image', '', $localization->value);
            }

            return $localization->value;
        }
    }

    private function getAttributeByName($name){
        $attr = $this->localize(app()->getLocale(), $name);

        if(empty($attr))
            $attr = isset($this->attributes[$name]) ? $this->attributes[$name] : '';

        return $attr;
    }

    public function __get($key){
        // Сначала проверяем, есть ли метод доступа (getter)
        $method = 'get' . Str::studly($key) . 'Attribute';
        if(method_exists($this, $method)){
            return $this->{$method}();
        }
        
        if(in_array($key, $this->localized_fields)){
            return $this->getAttributeByName($key);
        }

        return $this->getAttribute($key);
    }
}
