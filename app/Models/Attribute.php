<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Entity
{
    use SoftDeletes, HasLocalizationTrait;
    protected $dates = ['deleted_at'];

    public $entity_type = 'attribute';
    protected $table = 'attributes';

	protected $fillable = [
		'slug',
        'is_filter',
        'type',
        'required_for_all',
        'visible',
        'is_numeric_values',
        'is_variation_attribute',
        'unit'
	];

    protected $localized_fields = [
        'name',
        'filter_name'
    ];

    protected $types = [];

    public function __construct(array $attributes = []){
        parent::__construct($attributes);
        $this->types = [
            'multiple_checkboxes' => trans('locale.attributes.types.multiple_checkboxes'),
            'multiple_select' => trans('locale.attributes.types.multiple_select'),
            'single_radio' => trans('locale.attributes.types.single_radio'),
            'single_select' => trans('locale.attributes.types.single_select'),
            'multiple_color_checkboxes' => trans('locale.attributes.types.multiple_color_checkboxes'),
            'single_color_radio' => trans('locale.attributes.types.single_color_radio'),
            'range' => trans('locale.attributes.types.range'),
            'range_slider' => trans('locale.attributes.types.range_slider'),
            'yes_no' => trans('locale.attributes.types.yes_no'),
        ];
    }

    public function getFillable(){
        return $this->fillable;
    }

	// Связи
    public function values(){
		return $this->hasMany('App\Models\AttributeValue', 'attribute_id');
	}

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany('App\Models\Category', 'category_attributes', 'attribute_id', 'category_id');
    }

	protected static function boot() {
		parent::boot();

		self::deleted(function($model){
			$model->localization()->delete();
		});
	}

	public function getTypes(): array
    {
	    return $this->types;
    }

    public function getActionData(){
        return [
            'attributes' => [
                'id' => $this->id,
                'is_filter' => $this->is_filter
            ]
        ];
    }

    protected function dataMap(): array
    {
        return [
            'attributes' => [
                'id' => '',
                'slug' => ''
            ],
            'relations' => [
                'localization' => [
                    'attributes' => [
                        'id' => '',
                        'field' => '',
                        'language' => '',
                        'value' => ''
                    ]
                ],
                'values' => [
                    'attributes' => [
                        'id' => '',
                        'attribute_id' => '',
                        'value' => '',
                        'file_id' => ''
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
                ]
            ]
        ];
    }

    public function fieldsNames(): array
    {
        return [
            'relations.localization' => [
                'localization' => true,
                'fields' => [
                    [
                        'name' => 'Название',
                        'field' => 'name'
                    ]
                ]
            ],
            'attributes.slug' => [
                'name' => 'Слаг'
            ],
            'relations.values' => [
                'name' => 'Значение',
                'multiple' => true,
                'fields' => [
                    'relations.localization' => [
                        'localization' => true,
                        'fields' => [
                            [
                                'name' => 'Название',
                                'field' => 'name'
                            ]
                        ]
                    ],
                    'attributes.value' => [
                        'name' => 'Значение'
                    ],
                    'attributes.file_id' => [
                        'name' => 'Изображение',
                        'type' => 'file'
                    ]
                ]
            ]
        ];
    }
}
