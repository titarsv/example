<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use App\Helpers\Helper;
use App\Models\Concerns\HasCustomFields;
use App;

class Page extends Entity
{
    public $entity_type = 'page';
    protected $table = 'pages';
    protected $fillable = [
        'template',
        'parent_id',
        'status',
        'sort_order'
    ];

    use SoftDeletes, HasCustomFields;

    protected $dates = ['deleted_at'];

    public function parent(){
        return $this->belongsTo('App\Models\Page', 'parent_id');
    }

    public function seo(){
        return $this->morphOne('App\Models\Seo', 'seotable');
    }

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

        if(\Request::segment( 1 ) == 'admin'){
            $value = preg_replace('/<source.*?data-src="(.*?)" type="image\/(.*?)" \/>/s', '<source srcset="$1" type="image/$2" />', $value);
            return $value;
        }

        return str_replace('editor-image', '', $value);
    }

    private function getAttributeByName($name){
        return $this->localize(App::getLocale(), $name);
    }

    public function getNameAttribute(){
        return $this->getAttributeByName('name');
    }

    public function getBodyAttribute(){
        return $this->getAttributeByName('body');
    }

    public function saveSeo($request){
        $seo_data = $request->only(['canonical', 'robots']);
        if(!empty($request->url)){
            $seo_data['url'] = $request->url;
        }else{
            $name_key = 'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '');
            $seo_data['url'] = '/'.Str::slug(mb_strtolower(Helper::translit($request->$name_key)));
        }
        $seo_data['action'] = 'showAction';

        $seo = $this->seo;
        if(empty($seo)){
            $this->seo()->create($seo_data);
            $seo = $this->seo()->first();
        }else{
            $seo->fill($seo_data)->save();
        }

        $seo->saveLocalization($request);
    }

    public function link(){
        return $this->seo->link;
    }

    protected function dataMap(){
        return [
            'attributes' => [
                'id' => '',
                'template' => '',
                'parent_id' => '',
                'status' => '',
                'sort_order' => ''
            ],
            'relations' => [
                'seo' => [
                    'attributes' => [
                        'id' => '',
                        'canonical' => '',
                        'robots' => '',
                        'url' => ''
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
            ],
            'attributes.parent_id' => [
                'name' => 'Родительская страница'
            ],
            'attributes.sort_order' => [
                'name' => 'Порядок сортировки'
            ],
            'attributes.status' => [
                'name' => 'Статус'
            ],
            'relations.seo' => [
                'name' => 'SEO',
                'multiple' => true,
                'fields' => [
                    'attributes.url' => [
                        'name' => 'Url'
                    ],
                    'relations.localization' => [
                        'localization' => true,
                        'fields' => [
                            [
                                'name' => 'Название',
                                'field' => 'seo_name'
                            ],
                            [
                                'name' => 'Описание',
                                'field' => 'seo_description'
                            ],
                            [
                                'name' => 'Title',
                                'field' => 'meta_title'
                            ],
                            [
                                'name' => 'Meta description',
                                'field' => 'meta_description'
                            ],
                            [
                                'name' => 'Meta keywords',
                                'field' => 'meta_keywords'
                            ]
                        ]
                    ],
                    'attributes.canonical' => [
                        'name' => 'Canonical'
                    ],
                    'attributes.robots' => [
                        'name' => 'Robots'
                    ]
                ]
            ]
        ];
    }
}
