<?php

namespace App\Models;

use App\Helpers\Helper;
use Chelout\RelationshipEvents\Concerns\HasBelongsToManyEvents;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Redis;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Config;
use App;

class Product extends Entity
{
    use HasFactory, SoftDeletes, HasBelongsToManyEvents;

    protected $fillable = [
        'external_id',
        'name',
        'sku',
        'price',
        'original_price',
        'sale_price',
        'sale',
        'sale_from',
        'sale_to',
        'file_id',
        'stock',
        'visible',
        'sort_priority',
        'popularity',
        'rating'
    ];

    public function getFillable()
    {
        return $this->fillable;
    }

    // Автоматизация сохранения в Redis
    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            if (env('REDIS_CACHE')) {
                Redis::command('setbit', ["product_visible", $model->id, empty($model->attributes['visible']) ? 0 : 1]);
                Redis::command('setbit', ["product_stock", $model->id, $model->stock > 0 ? 1 : 0]);
                Redis::command('setbit', ["product_not_stock", $model->id, $model->stock === 0 ? 1 : 0]);
                Redis::command('setbit', ["product_under_the_order", $model->id, $model->stock < 0 ? 1 : 0]);
                Redis::command('zadd', ['prices', $model->price * 100, $model->id]);
                Redis::command('zadd', ['sort_priority', $model->sort_priority * 100, $model->id]);
                Redis::command('zadd', ['popularity', $model->popularity * 100, $model->id]);
                Redis::command('zadd', ['ratings', $model->ratings * 100, $model->id]);
            }

            // Эмбеддинг строится асинхронно (воркер очереди подхватит задачу уже после
            // того, как в этом же запросе будет сохранена локализация названия/описания)
            foreach (config('app.locales', [config('app.locale')]) as $locale) {
                \App\Jobs\GenerateProductEmbeddingJob::dispatch($model->id, $locale);
            }
        });

        self::updated(function ($model) {
            if (env('REDIS_CACHE')) {
                Redis::command('setbit', ["product_visible", $model->id, empty($model->attributes['visible']) ? 0 : 1]);
                Redis::command('setbit', ["product_stock", $model->id, $model->stock > 0 ? 1 : 0]);
                Redis::command('setbit', ["product_not_stock", $model->id, $model->stock === 0 ? 1 : 0]);
                Redis::command('setbit', ["product_under_the_order", $model->id, $model->stock < 0 ? 1 : 0]);
                Redis::command('zadd', ['prices', $model->price * 100, $model->id]);
                Redis::command('zadd', ['sort_priority', $model->sort_priority * 100, $model->id]);
                Redis::command('zadd', ['popularity', $model->popularity * 100, $model->id]);
                Redis::command('zadd', ['ratings', $model->ratings * 100, $model->id]);
            }
        });

        self::deleted(function ($model) {
            if (env('REDIS_CACHE')) {
                Redis::command('setbit', ["product_visible", $model->id, 0]);
                Redis::command('setbit', ["product_stock", $model->id, 0]);
                Redis::command('setbit', ["product_not_stock", $model->id, 0]);
                Redis::command('setbit', ["product_under_the_order", $model->id, 0]);
                Redis::command('zrem', ['prices', $model->id]);
                Redis::command('zrem', ['sort_priority', $model->id]);
                Redis::command('zrem', ['popularity', $model->id]);
                Redis::command('zrem', ['ratings', $model->id]);
            }
        });

        static::belongsToManyAttaching(function ($parent, $product, $related) {
            if (env('REDIS_CACHE')) {
                $product_id = $product->id;
                if ($parent == 'categories') {
                    $category = new Category();
                    foreach ($related as $category_id) {
                        $ids = $category->getParentCategories($category_id);
                        foreach ($ids as $id) {
                            if (!empty($id))
                                Redis::command('setbit', ["category_$id", $product_id, 1]);
                        }
                    }
                } elseif ($parent == 'values') {
                    foreach ($related as $value_id) {
                        Redis::command('setbit', ["attribute_$value_id", $product_id, 1]);
                    }
                } elseif ($parent == 'sales') {
                    foreach ($related as $value_id) {
                        Redis::command('setbit', ["sale_$value_id", $product_id, 1]);
                    }
                }
            }
        });

        static::belongsToManyDetaching(function ($parent, $product, $related) {
            if (env('REDIS_CACHE')) {
                $product_id = $product->id;
                if ($parent == 'values') {
                    foreach ($related as $value_id) {
                        Redis::command('setbit', ["attribute_$value_id", $product_id, 0]);
                    }
                } elseif ($parent == 'sales') {
                    foreach ($related as $value_id) {
                        Redis::command('setbit', ["sale_$value_id", $product_id, 0]);
                    }
                }
            }
        });

        static::belongsToManyDetached(function ($parent, $product, $related) {
            if (env('REDIS_CACHE')) {
                $product_id = $product->id;
                if ($parent == 'categories') {
                    $exclude = [];
                    foreach ($product->categories as $category) {
                        $category_id = $category->id;
                        $exclude = array_merge($exclude, $category->getParentCategories($category_id));
                    }

                    $category = new Category();
                    foreach ($related as $category_id) {
                        $ids = $category->getParentCategories($category_id);
                        foreach ($ids as $id) {
                            if (!in_array($id, $exclude))
                                Redis::command('setbit', ["category_$id", $product_id, 0]);
                        }
                    }
                }
            }
        });
    }

    public $entity_type = 'product';
    protected $table = 'products';
    protected $dates = ['deleted_at'];

    // Связи
    public function seo()
    {
        return $this->morphOne('App\Models\Seo', 'seotable');
    }

    public function categories()
    {
        return $this->belongsToMany('App\Models\Category', 'product_categories', 'product_id', 'category_id');
    }

    public function sales()
    {
        return $this->belongsToMany('App\Models\Sale', 'sale_products', 'product_id', 'sale_id')->withPivot('sale_price');
    }

    public function actual_sales()
    {
        $date = date('Y-m-d H:i:s');

        return $this->sales()->where('status', 1)
            ->where('show_from', '<=', $date)
            ->where('show_to', '>=', $date);
    }

    public function image()
    {
        return $this->hasOne('App\Models\File', 'id', 'file_id');
    }

    public function attributes()
    {
        return $this->hasMany('App\Models\ProductAttributes', 'product_id');
    }

    public function values()
    {
        return $this->belongsToMany('App\Models\AttributeValue', 'product_attributes', 'product_id', 'attribute_value_id')->withPivot('attribute_id');
    }

    // Связанные товары
    public function related()
    {
        return $this->belongsToMany('App\Models\Product', 'related_products', 'product_id', 'related_id');
    }

    // Похожие товары
    public function similar()
    {
        return $this->belongsToMany('App\Models\Product', 'similar_products', 'product_id', 'similar_id');
    }

    public function getAttributeById($id)
    {
        if (empty($this->values)) {
            $attr = $this->attributes()
                ->join('attributes', 'product_attributes.attribute_id', '=', 'attributes.id')
                ->where('attributes.id', $id)
                ->first();
            if (is_object($attr))
                return $attr->value;
        } else {
            foreach ($this->values as $val) {
                if ($val->attribute_id == $id) {
                    return $val;
                }
            }
        }

        return null;
    }

    // Данные вариаций
    public function variations_attributes()
    {
        $max_price = $this->price;
        $variations_attrs = [];
        $variations_prices = [];

        foreach ($this->variations as $variation) {
            $values = $variation->attribute_values;
            $variations_prices[implode('_', $values->pluck(['id'])->sort()->values()->all())] = [
                'original_price' => $variation->original_price,
                'price' => $variation->price,
                'id' => $variation->id
            ];
            if ($max_price < $variation->price) {
                $max_price = $variation->price;
            }
            foreach ($values as $value) {
                $attr = $value->attribute;
                if (!isset($variations_attrs[$attr->id])) {
                    $variations_attrs[$attr->id] = [
                        'name' => $attr->name,
                        'values' => [
                            $value->id => ['name' => $value->name . $attr->unit, 'stock' => $variation->stock, 'image' => $variation->file_id ? $variation->image->url() : $this->image->url()]
                        ]
                    ];
                } elseif (!isset($variations_attrs[$attr->id]['values'][$value->id])) {
                    $variations_attrs[$attr->id]['values'][$value->id] = ['name' => $value->name . $attr->unit, 'stock' => $variation->stock, 'image' => $variation->file_id ? $variation->image->url() : $this->image->url()];
                }
            }
        }

        $selected_variation_attributes = explode('_', array_key_first($variations_prices));

        return [
            'variations_prices' => $variations_prices,
            'variations_attrs' => $variations_attrs,
            'selected_variation_attributes' => $selected_variation_attributes
        ];
    }

    // Вариации
    public function variations()
    {
        return $this->hasMany('App\Models\Variation', 'product_id');
    }

    // Отзывы
    public function reviews()
    {
        return $this->hasMany('App\Models\Review', 'product_id');
    }

    public function wishlist()
    {
        return $this->hasMany('App\Models\Wishlist', 'product_id');
    }

//    public function similar(){
//        return $this->belongsToMany('App\Models\Product', 'similar_products', 'product_id', 'similar_id');
//    }

    public function products_cart()
    {
        return $this->hasOne('App\Models\ProductsCart', 'product_id');
    }

    public function galleries()
    {
        return $this->morphMany('App\Models\Gallery', 'parent');
    }

    public function gallery()
    {
        return $this->morphMany('App\Models\Gallery', 'parent')->where('field', 'gallery');
    }

    public function video_reviews()
    {
        return $this->morphMany('App\Models\Gallery', 'parent')->where('field', 'video_reviews');
    }

    public function localization()
    {
        return $this->morphMany('App\Models\Localization', 'localizable');
    }

    public function saveGalleries($request)
    {
        $gallery = new Gallery();
        $gallery->saveGalleries($request, $this, ['gallery']);
    }

    // Локализация
    public function saveLocalization($request)
    {
        $localization = new Localization();
        $localization->saveLocalization($request, $this, Helper::localizationFields(['name', 'description', 'characteristics']));
    }

    public function localize($language, $field)
    {
        $localization = $this->localization->first(function ($value, $key) use ($language, $field) {
            return $value->language == $language && $value->field == $field;
        });
        if (empty($localization))
            $localization = $this->localization()->where(['language' => $language, 'field' => $field])->first();

        if (empty($localization)) {
            return '';
        } else {
            return $localization->value;
        }
    }

    private function getAttributeByName($name)
    {
        return $this->localize(app()->getLocale(), $name);
    }

    public function getNameAttribute()
    {
        return $this->getAttributeByName('name');
    }

    public function getDescriptionAttribute()
    {
        return $this->getAttributeByName('description');
    }

    public function getCategoryAttribute()
    {
        if (isset($this->attributes['category'])) {
            $category = $this->attributes['category'];
        } else {
            $category = $this->categories()->where('categories.id', '!=', 1)->orderBy('parent_id', 'asc')->first();
            if (empty($category)) {
                $category = Category::find(1);
            }

            $this->category = $category;
        }
        return $category;
    }

    public function getCategoryNameAttribute()
    {
        $name = '';
        if (isset($this->relations['categories'])) {
            if ($this->categories->count()) {
                $name = $this->categories->first()->name;
            }
        } else {
            $category = $this->categories()->orderBy('parent_id', 'asc')->first();
            if (!empty($category)) {
                $name = $category->name;
            }
        }

        return $name;
    }

    public function getCategoryLinkAttribute()
    {
        $link = 'javascript:void(0)';
        $category = $this->categories()->orderBy('parent_id', 'asc')->first();
        if (!empty($category)) {
            $link = $category->link();
        }

        return $link;
    }

    public function getLabelsAttribute()
    {
        $labels = [];
        if (isset($this->relations['values'])) {
            foreach ($this->values as $label) {
                $labels[] = [
                    'name' => $label->name,
                    'color' => $label->value
                ];
            }
        } else {
            foreach ($this->values()->where('product_attributes.attribute_id', 1)->with('localization')->get() as $label) {
                $labels[] = [
                    'name' => $label->name,
                    'color' => $label->value
                ];
            }
        }

        return $labels;
    }

    public function getOriginalNameAttribute()
    {
        return $this->attributes['name'];
    }

    public function getGradeAttribute()
    {
        return round($this->reviews->avg('grade'));
    }

    public function getCurrentSaleAttribute()
    {
        if (array_key_exists('current_sale', $this->attributes)) {
            $sale = $this->attributes['current_sale'];
        } else {
            if (isset($this->relations['actual_sales'])) {
                $sale = $this->relations['actual_sales']->first();
            } else {
                $date = date('Y-m-d H:i:s');

                $sale = $this->sales()
                    ->where('status', 1)
                    ->where('show_from', '<=', $date)
                    ->where('show_to', '>=', $date)
                    ->first();
            }

            $this->current_sale = $sale;
        }

        return $sale;
    }

    public function getActionPriceAttribute()
    {
        $sale = $this->current_sale;

        if (!empty($sale)) {
            $price = round($sale->products()->where('product_id', $this->id)->whereNull('variation_id')->first()->pivot->sale_price, 2);
//            $price = round($this->price * (100 - $sale->sale_percent) / 100, 2);
//            if(!empty($this->outlet_price) && $this->outlet_price > $price){
//                return $this->outlet_price;
//            }

            return $price;
        }

        return null;
    }

    public function getActualPriceAttribute()
    {
        $action_price = $this->action_price;
        if (!empty($action_price)) {
            return $action_price;
        }

        return $this->price;
    }

    public function saveSeo($request)
    {
        $seo_data = $request->only(['canonical', 'robots']);
        if (!empty($request->url)) {
            $seo_data['url'] = $request->url;
        } else {
            $name_key = 'name'.(count(\Illuminate\Support\Facades\Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '');
            $seo_data['url'] = '/' . mb_strtolower(Helper::translit($request->$name_key));
        }
        $seo_data['action'] = 'showAction';

        $seo = $this->seo;
        if (empty($seo)) {
            $this->seo()->create($seo_data);
            $seo = $this->seo()->first();
        } else {
            $seo->fill($seo_data)->save();
        }

        $seo->saveLocalization($request);
    }

    public function link()
    {
        if (empty($this->seo)) {
            $request = new Request();
            $name = $this->name;
            $rd = [
                'url' => '/' . Str::slug(mb_strtolower(Helper::translit($name))),
                'seo_name_ru' => $name,
                'seo_name_ua' => $name,
                'meta_title_ru' => $name,
                'meta_title_ua' => $name
            ];
            $request->merge($rd);
            $this->saveSeo($request);
            $this->load('seo');

            return $this->seo->link;
        }
        return $this->seo->link;
    }

    public function getVideoAttribute()
    {
        $str = str_replace([
            'https://youtu.be/',
            'https://www.youtube.com/embed/',
            'https://www.youtube.com/watch?',
            '<iframe width="560" height="315" src="https://www.youtube.com/embed/',
            '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
        ], '', $this->attributes['video']);

        if (strpos($str, '&')) {
            foreach (explode('&', $str) as $param) {
                $temp = explode('=', $param);
                if ($temp[0] == 'v') {
                    $str = $temp[1];
                }
            }
        } elseif (strpos($str, '=')) {
            $temp = explode('=', $str);
            if ($temp[0] == 'v') {
                $str = $temp[1];
            }
        }

        return $str;
    }

    public function getVideoPreviewAttribute()
    {
        return '<img src="//img.youtube.com/vi/' . $this->video . '/mqdefault.jpg">';
    }

    public function getVideoData()
    {
        $api_key = env('GOOGLE_API_KEY');
        $video_id = $this->video;

        $data = json_decode($this->video_data, true);

        if (!empty($data) && time() - $data['time'] < 86400) {
            return $data['data'];
        }

        try {
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'debug' => true
                ]
            ]);
            $response = $client->request('GET', "https://www.googleapis.com/youtube/v3/videos?id=$video_id&key=$api_key&part=snippet,contentDetails,statistics,status", []);
            $body = $response->getBody();
            $status = true;
            $message = 'Data found!';
            $data = json_decode($body, true);
        } catch (RequestException $e) {
            $status = false;
            $message = $response->getMessage();
            $data = [];
        } catch (\Exception $e) {
            $status = false;
            $message = $e->getMessage();
            $data = [];
        }

        if ($status == false || !count($data['items'])) {
            return null;
        }

        $this->video_data = json_encode([
            'data' => $data['items'][0],
            'time' => time()
        ]);
        $this->save();

        return $data['items'][0];
    }

    public function getVideoMetaSnippet()
    {
        $item = $this->getVideoData();

        if (empty($item))
            return '';

        $snippet = [
            "@context" => "http://schema.org/",
            "@type" => "VideoObject",
            "name" => $item['snippet']['title'],
            "description" => $item['snippet']['description'],
            "thumbnailUrl" => $item['snippet']['thumbnails']['maxres']['url'],
            "duration" => $item['contentDetails']['duration'],
            "@id" => $item['id'],
            "datePublished" => date('Y-m-d', strtotime($item['snippet']['publishedAt'])),
            "uploadDate" => date('Y-m-d', strtotime($item['snippet']['publishedAt'])),
            "author" => [
                "@type" => "Person",
                "name" => $item['snippet']['channelTitle']
            ],
            "interactionStatistic" => [
                [
                    "@type" => "InteractionCounter",
                    "interactionService" => [
                        "@type" => "WebSite",
                        "name" => "YouTube",
                        "@id" => "https://youtube.com"
                    ],
                    "interactionType" => "http://schema.org/WatchAction",
                    "userInteractionCount" => $item['statistics']['viewCount']
                ],
                [
                    "@type" => "InteractionCounter",
                    "interactionService" => [
                        "@type" => "WebSite",
                        "name" => "YouTube",
                        "@id" => "https://youtube.com"
                    ],
                    "interactionType" => "http://schema.org/LikeAction",
                    "userInteractionCount" => $item['statistics']['likeCount']
                ]
            ]
        ];

        return '<script type="application/ld+json">' . json_encode($snippet) . '</script>';
    }

    public function in_wish()
    {
        if (isset($this->relations['wishlist'])) {
            if ($this->wishlist->count()) {
                return true;
            } else {
                return false;
            }
        } else {
            $user = Sentinel::check();

            if ($user) {
                if ($this->wishlist()->where('user_id', $user->id)->count()) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    public function main_category()
    {
        return $this->categories()->first();
    }

    /**
     * Поиск товаров
     *
     * @param string $text Поисковый текст
     * @param int $page Номер страницы
     * @param int $count Колличество на странице
     * @param bool $only_in_stock Только товары в наличии
     * @param null $sale_id ID акции
     * @param null $news_id ID новости
     * @param null $category_id ID категории
     * @return mixed
     */
    public function search($text = '', $page = 1, $count = 8, $only_in_stock = true, $sale_id = null, $news_id = null, $category_id = null)
    {
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $search = explode(' ', $text);
        $query = $this->select('products.*')
            ->join('localization', 'products.id', '=', 'localization.localizable_id')
            ->when($only_in_stock, function ($query) {
                $query->where('visible', 1);
            })
            ->where('localization.localizable_type', 'Products')
            ->where('localization.field', 'name')
            ->orderBy('products.id', 'desc')
            ->groupBy('products.id')
            ->with(['localization' => function ($query) {
                $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', 'ru')->where('field', 'name');
            }])
            ->with([
                'image.images',
                'values' => function ($query) {
                    $query->whereIn('product_attributes.attribute_id', [1, 2, 4])
                        ->with('attribute.localization');
                }
            ]);

        if (count($search) == 1) {
            $query->where(function ($query) use ($text) {
                $query->where('localization.value', 'like', '%' . $text . '%')
                    ->orWhere('external_id', 'like', '%' . $text . '%')
                    ->orWhere('products.name', 'like', '%' . $text . '%');
            });
        } else {
            $query->where(function ($query) use ($search) {
                foreach ($search as $s) {
                    $query->where('localization.value', 'like', '%' . $s . '%')
                        ->orWhere('external_id', 'like', '%' . $s . '%')
                        ->orWhere('products.name', 'like', '%' . $s . '%');
                }
            });
        }

        if (!empty($sale_id)) {
            $sale = Sale::find($sale_id);
            $exclude_ids = $sale->products()->select('products.id')->pluck('id')->toArray();
            $from = $sale->show_from;
            $to = $sale->show_to;

            $exclude_ids = array_merge($exclude_ids, $this->select('products.id')->where('sale', 1)->where('sale_from', '<=', $from)->where('sale_to', '>=', $to)->pluck('id')->toArray());
            $exclude_ids = array_merge($exclude_ids, $this->select('products.id')
                ->join('sale_products', 'products.id', '=', 'sale_products.product_id')
                ->join('sales', 'sale_products.sale_id', '=', 'sales.id')
                ->where(function ($query) use ($from) {
                    $query->where('sales.show_from', '>=', $from)
                        ->where('sales.show_from', '<=', $from);
                })
                ->orWhere(function ($query) use ($to) {
                    $query->where('sales.show_from', '>=', $to)
                        ->where('sales.show_from', '<=', $to);
                })
                ->pluck('id')
                ->toArray());

            $query->whereNotIn('products.id', $exclude_ids);
        }

        if (!empty($news_id)) {
            $news = News::find($news_id);
            $exclude_ids = $news->products()->select('products.id')->pluck('id')->toArray();
            $query->whereNotIn('products.id', $exclude_ids);
        }

        if (!empty($category_id)) {
            $category = new Category();
            $query->join('product_categories', 'products.id', '=', 'product_categories.product_id')->whereIn('product_categories.category_id', $category->getChildrenCategories($category_id));
        }

        $data = $query->paginate($count);

        return $data;
    }

    /**
     * Товары, которые чаще всего покупают вместе с текущим.
     */
    public function getBoughtTogether($limit = 8, $only_in_stock = true)
    {
        $ids = Redis::command('zrevrange', ["bought_with_{$this->id}", 0, ($limit * 2) - 1]);

        if (empty($ids)) {
            return collect();
        }

        $ids = array_map('intval', $ids);

        $products = self::whereIn('id', $ids)
            ->when($only_in_stock, function ($query) {
                $query->where('visible', 1);
            })
            ->with(['image.images'])
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(function ($id) use ($products) {
                return $products->get($id);
            })
            ->filter()
            ->take($limit)
            ->values();
    }

    /**
     * Собирает текстовый документ товара для построения эмбеддинга (текущая локаль).
     */
    public function getEmbeddingText(): string
    {
        $parts = [];

        if (!empty($this->name)) {
            $parts[] = $this->name;
        }

        if (!empty($this->category_name)) {
            $parts[] = $this->category_name;
        }

        $attributeParts = [];
        foreach ($this->attributes()->with('info.localization', 'value.localization')->get() as $attribute) {
            if (!empty($attribute->info) && $attribute->info->visible && !empty($attribute->value)) {
                $attributeParts[] = $attribute->info->name . ': ' . $attribute->value->name . $attribute->info->unit;
            }
        }
        if (!empty($attributeParts)) {
            $parts[] = implode(', ', $attributeParts);
        }

        $description = trim(strip_tags((string) $this->description));
        $description = self::stripMarketingBoilerplate($description);
        if (!empty($description)) {
            $parts[] = Str::limit($description, 2000, '');
        }

        return implode(". ", $parts);
    }

    /**
     * Вырезает шаблонные рекламные фразы (RU/UA), которые массово повторяются в описаниях
     * ("Заказывай на сайте ➦ забирай сегодня! ... ✔ Кэшбек ✔ Доставка ..."). У значительной
     * части каталога описание СОСТОИТ целиком из такого шаблона без уникального контента —
     * если включать это в эмбеддинг, разные товары получают одинаковый "рекламный" шум,
     * который забивает специфичный для товара сигнал и портит релевантность семантического поиска.
     */
    protected static function stripMarketingBoilerplate(string $text): string
    {
        $patterns = [
            '/Заказывай на сайте\s*➦\s*забирай сегодня!/ui',
            '/Замовляй на сайті\s*➦\s*забирай сьогодні!/ui',
            '/Всего за[\d\s]+грн!/ui',
            '/Всього за[\d\s]+грн!/ui',
            '/Доступная цена на/ui',
            '/Доступна ціна на/ui',
            '/[✔]\s*Кэшбек/ui',
            '/[✔]\s*Кешбэк/ui',
            '/[✔]\s*Кешбек/ui',
            '/[✔]\s*Доставка по Украине бесплатно в ближайший магазин\s*\S*/ui',
            '/[✔]\s*Доставка по Україні безкоштовно в найближчий магазин\s*\S*/ui',
            '/[✔]\s*Рассрочка и кредит/ui',
            '/[✔]\s*Розстрочка та кредит/ui',
            '/[✔]\s*Оплата частями/ui',
            '/[✔]\s*Оплата частинами/ui',
            '/ᐈ\s*Купить выгодно в интернет-магазине\s*\S*/ui',
            '/ᐈ\s*Купити вигідно в інтернет-магазині\s*\S*/ui',
        ];

        $text = preg_replace($patterns, '', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Персональные рекомендации на основе истории просмотров (cookie "viewed" — уже
     * используется на странице товара для блока "Недавно просмотренные").
     * Строит "профиль интересов" как среднее эмбеддингов просмотренных товаров и находит
     * ближайшие к нему. Если истории/эмбеддингов нет — возвращает пустую коллекцию
     * (вызывающий код должен сам сделать fallback на популярность — холодный старт).
     */
    public static function getPersonalizedRecommendations(array $viewedIds, $limit = 8, $only_in_stock = true)
    {
        if (empty($viewedIds)) {
            return collect();
        }

        $locale = App::getLocale();

        $viewedVectors = ProductEmbedding::where('locale', $locale)
            ->whereIn('product_id', $viewedIds)
            ->get(['vector'])
            ->map(fn($e) => $e->vector_array)
            ->filter();

        if ($viewedVectors->isEmpty()) {
            return collect();
        }

        $dimensions = count($viewedVectors->first());
        $profileVector = array_fill(0, $dimensions, 0.0);

        foreach ($viewedVectors as $vector) {
            foreach ($vector as $i => $value) {
                $profileVector[$i] += $value;
            }
        }
        foreach ($profileVector as $i => $value) {
            $profileVector[$i] = $value / $viewedVectors->count();
        }

        $results = self::rankProductsByVector($profileVector, $locale, $limit + count($viewedIds), $only_in_stock);

        return $results->whereNotIn('id', $viewedIds)->take($limit)->values();
    }

    /**
     * Семантический поиск товаров по смыслу запроса (эмбеддинги + косинусное сходство).
     */
    public static function semanticSearch(string $query, $limit = 20, $only_in_stock = true, $timeoutSeconds = 30)
    {
        $ai = app(\App\Services\AiServiceInterface::class);
        $queryVector = $ai->embed($query, $timeoutSeconds);

        if (empty($queryVector)) {
            return collect();
        }

        return self::rankProductsByVector($queryVector, App::getLocale(), $limit, $only_in_stock);
    }

    /**
     * Похожие товары по смыслу (эмбеддинг самого товара, а не текстового запроса).
     * Используется как fallback для "You may also like", когда для товара не подобраны
     * вручную similar_products.
     */
    public function getSimilarByEmbedding($limit = 8, $only_in_stock = true)
    {
        $locale = App::getLocale();

        $embedding = ProductEmbedding::where('product_id', $this->id)->where('locale', $locale)->first();
        if (empty($embedding)) {
            return collect();
        }

        return self::rankProductsByVector($embedding->vector_array, $locale, $limit, $only_in_stock, $this->id);
    }

    /**
     * Ранжирует товары по косинусному сходству эмбеддингов с заданным вектором.
     */
    protected static function rankProductsByVector(array $vector, string $locale, $limit, $only_in_stock = true, $excludeProductId = null)
    {
        $query = ProductEmbedding::where('locale', $locale);
        if (!empty($excludeProductId)) {
            $query->where('product_id', '!=', $excludeProductId);
        }
        $query->whereHas('product', function ($q) use ($only_in_stock) {
            if ($only_in_stock) {
                $q->where('visible', 1);
            }
        });

        $embeddings = $query->get(['product_id', 'vector']);

        $scored = $embeddings->map(function ($embedding) use ($vector) {
            return [
                'product_id' => $embedding->product_id,
                'score' => self::cosineSimilarity($vector, $embedding->vector_array),
            ];
        })->sortByDesc('score')->take($limit);

        $products = self::whereIn('id', $scored->pluck('product_id'))->with(['image.images'])->get()->keyBy('id');

        return $scored
            ->map(function ($item) use ($products) {
                return $products->get($item['product_id']);
            })
            ->filter()
            ->values();
    }

    protected static function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    public function updateAttributes($product_attributes)
    {
        // Обновление коллекций атрибутов Redis
        if (env('REDIS_CACHE')) {
            $values = [];
            if (!empty($product_attributes)) {
                foreach ($product_attributes as $attr) {
                    $values = array_merge($values, $attr['values']);
                }
            }
            $current_values = $this->values->pluck('id')->toArray();
            foreach ($current_values as $value_id) {
                Redis::command('setbit', ["attribute_$value_id", $this->id, 0]);
            }
            foreach ($values as $i => $value_id) {
                Redis::command('setbit', ["attribute_$value_id", $this->id, 1]);
            }
        }

        $values = [];
        if (!empty($product_attributes)) {
            foreach ($product_attributes as $attribute) {
                foreach ($attribute['values'] as $value) {
                    $values[$value] = ['attribute_id' => $attribute['id']];
                }
            }
        }

        $this->values()->sync($values);
    }

    public function getAttributesArray()
    {
        $attributes = [];
        foreach ($this->values as $value) {
            if (!isset($attributes[$value->attribute->name])) {
                $attributes[$value->attribute->name] = [];
            }

            $attributes[$value->attribute->name][] = $value->name;
        }

        return $attributes;
    }

//    public function similar(){
//	    $locale = App::getLocale();
//
//	    if(empty($category = $this->categories->first())){
//	        return null;
//        }
//
//    	return $category->products()->limit(4)->where('products.id', '!=', $this->id)->with([
//		    'image.images',
//		    'values' => function($query){
//			    $query->whereIn('product_attributes.attribute_id', [1, 2, 4])
//			          ->with('attribute.localization');
//		    },
//		    'localization' => function($query) use ($locale){
//			    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale)->where('field', 'name');
//		    }
//	    ])->get();
//    }

    public function getProducts($ids)
    {
        $locale = App::getLocale();
        $id = $this->id;

        return $this->select('*')
            ->limit(4)
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '!=', $id);
            })
            ->whereIn('products.id', $ids)->with([
                'image.images',
                'values' => function ($query) {
                    $query->whereIn('product_attributes.attribute_id', [1, 2, 4])
                        ->with('attribute.localization');
                },
                'localization' => function ($query) use ($locale) {
                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale)->where('field', 'name');
                }
            ])->get();
    }

    /**
     * Получение вариаций (атрибутов с дополнительной стоимостью)
     *
     * @return array
     */
    public function get_variations()
    {
        $variations = [];
        $attributes = $this->get_attributes->groupBy('attribute_id');

        foreach ($attributes as $attribute => $values) {
            foreach ($values as $value) {
                if ($value->price > 0) {
                    $variations[$attribute] = $values->sortBy('price')->values();
                    continue;
                }
            }
        }

        return $variations;
    }

    public function getReviews($count, $page, $paginator_options = [])
    {
        return new LengthAwarePaginator(
            $this->reviews()
                ->where('published', 1)
                ->limit($count)
                ->offset($count * ($page - 1))
                ->orderBy('created_at', 'desc')
                ->get(),
            $this->reviews()->where('published', 1)->count(),
            $count,
            $page,
            $paginator_options
        );
    }

    /**
     * Значения атрибута товара
     *
     * @param $slug
     * @return array
     */
    public function get_attribute($slug)
    {
        $attr_val = new AttributeValue;
        $attribute = $attr_val->select('localization.value as name')
            ->join('product_attributes', 'product_attributes.attribute_value_id', '=', 'attribute_values.id')
            ->join('attributes', 'attributes.id', '=', 'attribute_values.attribute_id')
            ->join('localization', 'attribute_values.id', '=', 'localization.localizable_id')
            ->where('attributes.slug', $slug)
            ->where('product_attributes.product_id', $this->id)
            ->where('localization.localizable_type', 'Values')
            ->where('localization.language', App::getLocale())
            ->first();

        if (!empty($attribute))
            return $attribute->attributes['name'];

        return null;
    }

    public function getThcAttribute(){
        return $this->getAttributeById(3);
    }

    public function getCbdAttribute(){
        return $this->getAttributeById(4);
    }

    public function getActionData(){
        return [
            'attributes' => [
                'id' => $this->id,
                'visible' => $this->visible
            ]
        ];
    }

    protected function dataMap()
    {
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
                'categories' => [
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
                'values' => [
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
                ],
                'variations' => [
                    'attributes' => [
                        'id' => '',
                        'price' => '',
                        'stock' => ''
                    ],
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
                ],
                'image' => [
                    'attributes' => [
                        'id' => '',
                        'path' => ''
                    ]
                ],
                'gallery' => [
                    'attributes' => [
                        'id' => '',
                        'field' => '',
                        'file_id' => ''
                    ],
                    'relations' => [
                        'image' => [
                            'attributes' => [
                                'id' => '',
                                'path' => ''
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

    public function fieldsNames()
    {
        return [
            'relations.localization' => [
                'localization' => true,
                'fields' => [
                    [
                        'name' => 'Название',
                        'field' => 'name'
                    ],
                    [
                        'name' => 'Описание товара',
                        'field' => 'description'
                    ]
                ]
            ],
            'relations.image[].attributes.path' => [
                'name' => 'Изображение',
                'type' => 'file'
            ],
            'relations.gallery' => [
                'name' => 'Галлерея',
                'multiple' => true,
                'fields' => [
                    'relations.image[].attributes.path' => [
                        'name' => 'Фото',
                        'type' => 'file'
                    ]
                ]
            ],
            'attributes.sku' => [
                'name' => 'Артикул'
            ],
            'attributes.original_price' => [
                'name' => 'Цена'
            ],
            'attributes.sale' => [
                'name' => 'Акционная цена'
            ],
            'attributes.sale_price' => [
                'name' => 'Цена со скидкой'
            ],
            'attributes.sale_from' => [
                'name' => 'Скидка с'
            ],
            'attributes.sale_to' => [
                'name' => 'Скидка до'
            ],
            'attributes.temporary' => [
                'name' => 'Временный'
            ],
            'attributes.temporary_from' => [
                'name' => 'Временный с'
            ],
            'attributes.temporary_to' => [
                'name' => 'Временный до'
            ],
            'attributes.stock' => [
                'name' => 'Наличие товара'
            ],
            'attributes.series' => [
                'name' => 'Серия'
            ],
            'attributes.certificate' => [
                'name' => 'Это сертификат'
            ],
            'relations.categories' => [
                'name' => 'Категория',
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
                    ]
                ]
            ],
            'relations.values' => [
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
            'relations.variations' => [
                'name' => 'Вариация',
                'multiple' => true,
                'fields' => [
                    'attributes.price' => [
                        'name' => 'Цена'
                    ],
                    'attributes.stock' => [
                        'name' => 'Наличие вариации'
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
                    ]
                ]
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
