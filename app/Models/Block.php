<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Models\Concerns\HasCustomFields;
use Illuminate\Database\Eloquent\SoftDeletes;
use App;

class Block extends Entity
{
    public $entity_type = 'block';
    protected $table = 'blocks';
    protected $fillable = [
        'template'
    ];

    use SoftDeletes, HasCustomFields;

    protected $dates = ['deleted_at'];

    public function localization(){
        return $this->morphMany('App\Models\Localization', 'localizable');
    }

    public function saveLocalization($request){
        $localization = new Localization();
        $localization->saveLocalization($request, $this, Helper::localizationFields(['name', 'body']));
    }

    public function localize($language, $field){
        $localization = $this->localization->first(function ($value, $key) use ($language, $field){
            return $value->language == $language && $value->field == $field;
        });
        if(empty($localization) && !isset($this->relations['localization']))
            $localization = $this->localization()->where(['language' => $language, 'field' => $field])->first();

        if(empty($localization)) {
            $value = '';
        }else{
            $value = $localization->value;
        }

        if(request()->segment(1) == 'admin'){
            $value = preg_replace('/<source.*?data-src="(.*?)" type="image\/(.*?)" \/>/s', '<source srcset="$1" type="image/$2" />', $value);
            return $value;
        }

        return str_replace('editor-image', '', $value);
    }

    private function getAttributeByName($name){
        return $this->localize(app()->getLocale(), $name);
    }

    public function getNameAttribute(){
        return $this->getAttributeByName('name');
    }

    public function getBodyAttribute(){
        return $this->getAttributeByName('body');
    }

    protected function dataMap(){
        return [
            'attributes' => [
                'id' => '',
                'template' => ''
            ],
            'relations' => [
                'localization' => [
                    'attributes' => [
                        'id' => '',
                        'field' => '',
                        'language' => '',
                        'value' => ''
                    ]
                ]
            ]
        ];
    }

    public function fieldsNames(){
        return [
            'relations.localization' => [
                'localization' => true,
                'fields' => [
                    [
                        'name' => 'Название',
                        'field' => 'name'
                    ],
                    [
                        'name' => 'Контент',
                        'field' => 'body'
                    ]
                ]
            ],
            'attributes.template' => [
                'name' => 'Шаблон'
            ]
        ];
    }
}
