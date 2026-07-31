<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\SoftDeletes;
use App;

class Block extends Entity
{
    public $entity_type = 'block';
    protected $table = 'blocks';
    protected $fillable = [
        'template'
    ];

    use SoftDeletes;

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
        if(empty($localization))
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

    /**
     * Подгрузка товаров в данные
     *
     * @param $fields
     *
     * @return mixed
     */
    public function setFieldsProducts($fields){
        $products = new Product();

        if(empty($fields)){
            $fields = [];
        }

        foreach($fields as $i => $field){
            if($field->type == 'repeater'){
                $fields[$i]->data = $this->setRepeaterProducts($fields[$i]->fields, $fields[$i]->data);
            }elseif($field->type == 'product'){
                if(!empty($field->value)){
                    $fields[$i]->value = [
                        'id' => $field->value,
                        'product' => $products->find($field->value)
                    ];
                }
            }
        }

        return $fields;
    }

    /**
     * Подгрузка товаров в данные повторителя
     *
     * @param $fields
     * @param $data
     *
     * @return mixed
     */
    public function setRepeaterProducts($fields, $data){
        foreach($data as $i => $fields_data){
            foreach($fields as $field){
                if(isset($fields_data->{$field->slug})){
                    if($field->type == 'repeater'){
                        $data[$i]->{$field->slug} = $this->setRepeaterProducts($field->fields, $fields_data->{$field->slug});
                    }elseif($field->type == 'product'){
                        $products = new Product();
                        $data[$i]->{$field->slug} = [
                            'id' => $fields_data->{$field->slug},
                            'product' => $products->find($fields_data->{$field->slug})
                        ];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Подгрузка изобрпжений в данные
     *
     * @param $fields
     *
     * @return mixed
     */
    public function setFieldsImages($fields){
        $images = new File();

        if(empty($fields)){
            $fields = [];
        }

        foreach($fields as $i => $field){
            if($field->type == 'repeater'){
                $fields[$i]->data = $this->setRepeaterImages($fields[$i]->fields, $fields[$i]->data);
            }elseif($field->type == 'oembed'){
                if(!empty($field->value)){
                    $fields[$i]->value = [
                        'id' => $field->value,
                        'image' => $images->find($field->value)
                    ];
                }
            }
        }

        return $fields;
    }

    /**
     * Подгрузка изобрпжений в данные повторителя
     *
     * @param $fields
     * @param $data
     *
     * @return mixed
     */
    public function setRepeaterImages($fields, $data){
        foreach($data as $i => $fields_data){
            foreach($fields as $field){
                if(isset($fields_data->{$field->slug})){
                    if($field->type == 'repeater'){
                        $data[$i]->{$field->slug} = $this->setRepeaterImages($field->fields, $fields_data->{$field->slug});
                    }elseif($field->type == 'oembed'){
                        $images = new File();
                        $data[$i]->{$field->slug} = [
                            'id' => $fields_data->{$field->slug},
                            'image' => $images->find($fields_data->{$field->slug})
                        ];
                    }
                }
            }
        }

        return $data;
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
