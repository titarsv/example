<?php

namespace App\Models;

use App;
use App\Helpers\Helper;

class AttributeValue extends Entity
{
	protected $fillable = [
		'attribute_id',
		'value',
        'file_id'
	];

	protected $table = 'attribute_values';
	public $timestamps = false;

    protected static function boot() {
        parent::boot();

        self::deleted(function($model){
            $model->localization()->delete();
        });
    }

	// Связи
	public function attribute(){
		return $this->belongsTo('App\Models\Attribute', 'attribute_id');
	}

	public function localization(){
		return $this->morphMany('App\Models\Localization', 'localizable');
	}

	public function products(){
        return $this->belongsToMany('App\Models\Product', 'product_attributes', 'attribute_value_id', 'product_id');
    }

    public function image(){
        return $this->hasOne('App\Models\File', 'id', 'file_id');
    }

	// Локализация
	public function saveLocalization($request){
		$localization = new Localization();
		$localization->saveLocalization($request, $this, Helper::localizationFields(['name']));
	}

	public function localize($language, $field){
        $localization = $this->localization->first(function ($value, $key) use ($language, $field){
            return $value->language == $language && $value->field == $field;
        });
        if(empty($localization))
            $localization = $this->localization()->where(['language' => $language, 'field' => $field])->first();

		if(empty($localization)){
			return '';
		}else{
			return $localization->value;
		}
	}

	private function getAttributeByName($name){
		return $this->localize(App::getLocale(), $name);
	}

	public function getNameAttribute(){
		return $this->getAttributeByName('name');
	}

    protected function dataMap(){
        return [
            'attributes' => [],
            'relations' => [
                'attribute' => [
                    'attributes' => [],
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
                ],
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
}
