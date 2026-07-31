<?php

namespace App\Models;

class Variation extends Entity
{
    protected $fillable = [
        'external_id',
        'product_id',
        'file_id',
        'stock',
        'price',
        'original_price',
        'sale_price',
        'sale',
        'sale_from',
        'sale_to'
    ];

    protected $table = 'variations';
    public $timestamps = false;

    public function attribute_values(){
        return $this->belongsToMany('App\Models\AttributeValue', 'variation_attributes');
    }

    public function product(){
        return $this->belongsTo('App\Models\Product', 'product_id');
    }

    public function image(){
        return $this->hasOne('App\Models\File', 'id', 'file_id');
    }

    public function getFromAttribute(){
        return !empty($this->sale_from) ? date('d.m.Y', strtotime($this->sale_from)) : '';
    }

    public function getToAttribute(){
        return !empty($this->sale_to) ? date('d.m.Y', strtotime($this->sale_to)) : '';
    }

    protected function dataMap(){
        return [
            'attributes' => [],
            'relations' => [
                'attribute_values' => [
                    'attributes' => [
                        'id' => '',
                        'attribute_id' => ''
                    ],
                    'relations' => [
                        'attribute' => [
                            'attributes' => [
                                'id' => ''
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
                ]
            ]
        ];
    }

    public function fieldsNames(){
        return [
            'external_id' => [
                'name' => 'Внешний ID'
            ],
            'product_id' => [
                'name' => 'ID товара'
            ],
            'file_id' => [
                'name' => 'ID изображения'
            ],
            'attributes.stock' => [
                'name' => 'Наличие вариации'
            ],
            'attributes.price' => [
                'name' => 'Цена'
            ],
            'attributes.original_price' => [
                'name' => 'Базовая цена'
            ],
            'attributes.sale_price' => [
                'name' => 'Акционная цена'
            ],
            'attributes.sale' => [
                'name' => 'Актуальность акционной цены'
            ],
            'attributes.sale_from' => [
                'name' => 'Начало акции'
            ],
            'attributes.sale_to' => [
                'name' => 'Конец акции'
            ],
            'relations.attribute_values' => [
                'name' => 'Атрибут',
                'multiple' => true,
                'fields' => [
                    'relations.localization' => [
                        'localization' => true,
                        'fields' => [
                            [
                                'name_from' => 'relations.attribute[].relations.localization[].attributes.value',
                                'name' => 'Название',
                                'field' => 'name'
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
