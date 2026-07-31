<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Localization extends Model
{
    public $entity_type = 'localization';
    protected $table = 'localization';

    protected $fillable = [
        'field',
        'language',
        'value',
        'localizable_type',
        'localizable_id',
    ];

    public function localizable(){
        return $this->morphTo();
    }

    public function saveLocalization($request, $parent, $fields = []){
        $locales = Config::get('app.locales');
        $langs = [];
        foreach($locales as $locale){
            $langs['_'.$locale] = $locale;
        }
        $localizations = [];
        foreach($request->only($fields) as $key => $val){
            $without_p = preg_replace('/<p>\s*?(<a .*?><picture.*?><\/a>|<picture.*?>)?\s*<\/p>/s', '<figure>$1</figure>', $val);
            $lazy = preg_replace('/<source srcset="(.*?)" type="image\/(.*?)" \/>/s', '<source srcset="/images/pixel.$2" data-src="$1" type="image/$2" />', $without_p);
            $val = $lazy;

            $lang = substr($key, -3);
            if(isset($langs[$lang])){
                $lang = $langs[$lang];
                $key = substr($key, 0, -3);
            }else{
                $lang = Config::get('app.main_locale');
            }
            $localization_data = [
                'language' => $lang,
                'field' => $key,
                'value' => $val
            ];
            if(!isset($localizations[$lang])){
                $localizations[$lang] = [];
            }
            if(!isset($localizations[$lang][$key]))
                $localizations[$lang][$key] = $parent->localization()->where('language', $lang)->where('field', $key)->first();
            if(empty($localizations[$lang][$key]) && !empty($val)){
                $parent->localization()->create($localization_data);
            }elseif(!empty($localizations[$lang][$key])){
                $localizations[$lang][$key]->fill($localization_data)->save();
            }
        }
    }
}
