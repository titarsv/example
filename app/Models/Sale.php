<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Config;
use App;

class Sale extends Entity
{
    use SoftDeletes;

    protected $fillable = [
    	'sale_percent',
        'file_id',
//        'file_xs_id',
//        'preview_id',
//        'banner_color',
        'status',
        'show_from',
        'show_to'
    ];

    protected $dates = ['deleted_at'];

    public $entity_type = 'sale';
    protected $table = 'sales';

    // Автоматизация
	protected static function boot() {
		parent::boot();

		self::deleted(function($model){
			$model->localization()->delete();
		});
	}

	// Связи
	public function seo(){
		return $this->morphOne('App\Models\Seo', 'seotable');
	}

    public function image(){
        return $this->hasOne('App\Models\File', 'id', 'file_id');
    }

    public function image_xs(){
        return $this->hasOne('App\Models\File', 'id', 'file_xs_id');
    }

    public function preview(){
        return $this->hasOne('App\Models\File', 'id', 'preview_id');
    }

    public function products(){
        return $this->belongsToMany('App\Models\Product', 'sale_products', 'sale_id', 'product_id')->withPivot('sale_price');
    }

	public function localization(){
		return $this->morphMany('App\Models\Localization', 'localizable');
	}

	// Локализация
	public function saveLocalization($request){
		$localization = new Localization();
		$localization->saveLocalization($request, $this, Helper::localizationFields(['name', 'subtitle', 'body']));
	}

	public function localize($language, $field){
        $localization = $this->localization->first(function ($value, $key) use ($language, $field){
            return $value->language == $language && $value->field == $field;
        });
        if(empty($localization) && !isset($this->relations['localization']))
            $localization = $this->localization()->where(['language' => $language, 'field' => $field])->first();

		if(empty($localization)) {
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

//    public function getSubtitleAttribute(){
//        return $this->getAttributeByName('subtitle');
//    }

	public function getBodyAttribute(){
		return $this->getAttributeByName('body');
	}

	// Сео
	public function saveSeo($request){
        $seo_data = $request->only(['canonical', 'robots']);
        if(!empty($request->url)){
            $seo_data['url'] = $request->url;
        }else{
            $name_key = 'name'.(count(\Illuminate\Support\Facades\Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '');
            $seo_data['url'] = '/'.mb_strtolower(translit($request->$name_key));
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
		return $this->seo->getLinkAttribute();
	}

	public function getLeftTextAttribute(){
        date_default_timezone_set('Europe/Kiev');
	    $time = strtotime($this->show_to) - time();
	    $seconds = $time%60 < 10 ? '0'.$time%60 : $time%60;
        $time = ($time - $seconds)/60;
	    $minutes = $time%60 < 10 ? '0'.$time%60 : $time%60;
        $time = ($time - $minutes)/60;
        $hours = $time%24 < 10 ? '0'.$time%24 : $time%24;
        $days = ($time - $hours)/24;
	    $text = "$days ".trans_choice('date.days', $days)." $hours:$minutes:$seconds";
	    return $text;
    }

    public function getPeriodAttribute(){
        date_default_timezone_set('Europe/Kiev');
        $locale = App::getLocale();
	    $months = [
	        'ru' => [
                '01' => 'января',
                '02' => 'февраля',
                '03' => 'марта',
                '04' => 'апреля',
                '05' => 'мая',
                '06' => 'июня',
                '07' => 'июля',
                '08' => 'августа',
                '09' => 'сентября',
                '10' => 'октября',
                '11' => 'ноября',
                '12' => 'декабря'
            ],
            'ua' => [
                '01' => 'січня',
                '02' => 'лютого',
                '03' => 'березня',
                '04' => 'квітня',
                '05' => 'травня',
                '06' => 'червня',
                '07' => 'липня',
                '08' => 'серпня',
                '09' => 'вересня',
                '10' => 'жовтня',
                '11' => 'листопада',
                '12' => 'грудня'
            ]
        ];
	    $from = strtotime($this->show_from);
	    $to = strtotime($this->show_to);
        return date('d '.$months[$locale][date('m', $from)].' Y', $from).' — '.date('d '.$months[$locale][date('m', $to)].' Y', $to);
    }

	public function productsList($current_page = 1){
        $limit = 20;
        $products = new LengthAwarePaginator(
            $this->products()->select(['products.id', 'products.sku', 'products.stock', 'products.original_price', 'products.file_id', 'localization.value as name'])
                ->leftJoin('localization', function($leftJoin) {
                    $leftJoin->on('products.id', '=', 'localization.localizable_id')
                        ->where('localization.localizable_type', '=', 'Products')
                        ->where('localization.language', '=', config('locale'))
                        ->where('field', 'name');
                })
                ->with(['attributes.info', 'attributes.value'])
                ->limit($limit)
                ->offset($limit * ($current_page - 1))
                ->get(),
            $this->products()->count(),
            $limit,
            $current_page,
            [
                'path' => url('/admin/sales/edit/'.$this->id)
            ]
        );

        return $products;
    }

    protected function dataMap(){
        return [
            'attributes' => [],
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
                'image' => [
                    'attributes' => [
                        'id' => '',
                        'path' => ''
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
                        'name' => 'Описание',
                        'field' => 'description'
                    ]
                ]
            ],
            'relations.image[].attributes.path' => [
                'name' => 'Изображение',
                'type' => 'file'
            ],
            'attributes.banner_color' => [
                'name' => 'Цвет банера'
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
