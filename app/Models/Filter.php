<?php

namespace App\Models;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Redis;
use App;

class Filter
{
	protected $enable_redis = false;
	// Текущая категория
    protected $category = null;
    // Массив отмеченных категорий
    protected $categories = [];
	// Массив отмеченных категорий включая дочерние
	protected $categories_with_children = [];
    // Коллекция фильтров доступных в текущей категории
    protected $product_attributes = null;
    // Необработанная строка фильтров
    protected $path = '';
    // Массив атрибутов для фильтрации товаров
    protected $filtered = [];
    // Коллекция отфильтрованных товаров
    protected $products = null;
    // Фильтры для отображения
	protected $attributes = [];
	// Диапазоны цен доступные для фильтрации
	protected $price_ranges = [];
    // Выбранный диапазон цен
    protected $price = [];
    // Минимальная цена в текущей категории
    protected $min_price = 0;
    // Максимальная цена в текущей категории
    protected $max_price = 0;
	// Битмапы текущих фильтров
	protected $redis_keys = [];
    // Неопределённые параметры
    protected $undefined_params = [];
    // Текущая страница пагинации
    protected $page = 1;
    // Текущая распродажа
    protected $sale_id = null;
    // Поисковый запрос
    protected $search_text = '';
    // Может быть несколько категорий
    protected $allow_multi_categories = false;
    // ID корневой категории
    protected $root_id = 1;
    // Наличие
    protected $stock = null;
    protected $not_stock = null;
    public $with_price_filter = false;
    public $with_stock_filter = false;
    protected $banners_count = 0;
    protected $banners_displayed_count = 0;
    public $only_sales = false;

	function __construct($category = null){
//		$this->enable_redis = env('REDIS_CACHE');
		$this->enable_redis = true;
		if(!empty($category)){
			$this->setCategory($category);
		}
	}

    /**
     * Установка текущей категории
     *
     * @param $cat
     * @return $this
     */
    public function setCategory($cat){
    	if(is_int($cat)){
		    $this->category = Category::find($cat);
	    }elseif(is_string($cat)){
		    $this->category = Category::where('url_alias', $cat);
	    }elseif(is_object($cat) && $cat instanceof Category){
		    $this->category = $cat;
	    }

        $this->setProductAttributes();
        $this->setPrice();
        return $this;
    }

    /**
     * Установка наличия
     *
     * @param $stock
     * @return $this
     */
    public function setStock($stock){
        if(count($stock) == 1){
            if($stock[0])
                $this->stock = 1;
            else
                $this->not_stock = 1;
        }

        return $this;
    }

    /**
     * Установка страницы пагинации
     *
     * @param $page
     * @return $this
     */
    public function setPage($page){
        $this->page = $page;

        return $this;
    }

    /**
     * Получение текущей страницы пагинации
     *
     * @return int
     */
    public function getPage(){
        return $this->page;
    }

    /**
     * Установка ID распродажи
     *
     * @param $sale_id
     * @return $this
     */
    public function setSale($sale_id){
        $this->sale_id = $sale_id;
        return $this;
    }

    public function setIsSale(){
        $this->only_sales = 1;
        return $this;
    }

    public function isSale(){
        return $this->only_sales;
    }

    /**
     * Установка дополнительных параметров
     *
     * @param $param
     *
     * @return $this
     */
    public function setParam($param){
        if(strpos($param, 'page-') === 0){
            $this->page = (int)str_replace('page-', '', $param);

            return $this;
        }elseif($param == 'sale'){
            $this->only_sales = true;

            return $this;
        }else{
            $params = explode('--', $param);

            foreach($params as $param){
                if(strpos($param, 'categories-') === 0) {
                    $model = new Category();
                    $categories = [];
                    foreach (explode('-', str_replace('categories-', '', $param)) as $slug) {
                        $item = $model->where('slug', $slug)->with('children')->first();
                        if (!empty($item)) {
                            $categories[] = $item;
                        } else {
                            $this->undefined_params[] = $slug;
                        }
                    }
                    $this->categories = $categories;
                }elseif(strpos($param, 'price-') === 0){
                    $this->with_price_filter = true;
                    $this->setPrice($param);
                }elseif($param =='stock-1'){
                    $this->with_stock_filter = true;
                    $this->stock = 1;
                }elseif($param =='stock-0'){
                    $this->with_stock_filter = true;
                    $this->not_stock = 1;
                }else{
                    $parts = explode('_', $param);
                    $attribute = Attribute::where('slug', $parts[0])->first();
                    if(empty($attribute)){
                        $this->undefined_params[] = $param;
                    }else{
                        unset($parts[0]);
                        $values = $attribute->values()->select('id')->whereIn('value', $parts)->pluck('id')->toArray();
                        if(count($values) < count($parts)){
                            $this->undefined_params[] = $param;
                        }else{
                            $this->filtered[$attribute->id] = $values;
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Установка атрибутов фильтрации
     *
     * @param $ids
     * @return $this
     */
    public function setAttributesValues($ids){
        $this->filtered = [];
        if(!empty($ids)){
            $values = AttributeValue::whereIn('id', $ids)->get();
            foreach($values as $value){
                $this->filtered[$value->attribute_id][] = $value->id;
            }
        }

        return $this;
    }

    /**
     * Получение неопределённых атрибутов
     *
     * @return array
     */
    public function getUndefined(){
        return $this->undefined_params;
    }

    /**
     * Получение банеров каталога
     *
     * @param $limit
     * @param $page
     * @return array
     */
    public function getBanners($limit, $page){
        $min_position = $limit * ($page - 1) + 1;
        $max_position = $limit * $page;
        $banners = [
            'list' => [],
//            'sidebar' => [],
//            'background' => []
        ];
        $category = $this->category;
        $query = BannerPosition::when($category, function($query) use($category){
                return $query->where(function($query) use($category){
                    return $query->where('category_id', $category->id)->orWhere('category_id', 0);
                });
            })
            ->join('banners', 'banners.id', '=', 'banner_positions.banner_id')
            ->where('banners.from', '<=', date('Y-m-d'))
            ->where('banners.lang', app()->getLocale())
            ->where(function($query){
                $query->where('banners.to', '>', date('Y-m-d'))
                    ->orWhereNull('banners.to');
            });

        $this->banners_displayed_count = $query->clone()->where('banner_positions.position', '<', $min_position)->count();

        $positions = $query->whereBetween('banner_positions.position', [$min_position, $max_position])
            ->with('banner')
            ->with(['banner' => function($query){
                $query->with('image');
            }])
            ->get();

        $count = 0;
        foreach($positions as $position){
            $banners['list'][$position->position - ($limit * ($page -1)) - $count] = $position->banner;
            $count++;
        }

        $this->banners_count = $count;

        return $banners;
    }

    /**
     * Установка параметров
     *
     * @param $request_params
     * @return $this
     */
    public function setRequest($request_params){
        $this->params = $request_params;
        $this->setPrice();
        return $this;
    }

    /**
     * Установка параметров фильтрации из ЧПУ
     *
     * @param $path
     * @return $this
     */
    public function setFilterPath($path){
        $this->path = $path;
        $this->setFiltered();
        $this->setPrice();
        return $this;
    }

    /**
     * Получить данные для фильтрации товаров
     *
     * @return array
     */
    public function getFilter(){
        return $this->filtered;
    }

    /**
     * Подгрузка атрибутов категории из БД
     */
    public function setProductAttributes(){
        if(!empty($this->category)) {
            $this->product_attributes = $this->category->attributes()->with([
                'values.localization',
                'localization'
            ])->get();
        }else{
            $this->product_attributes = Attribute::where('is_filter', 1)->where('required_for_all', 1)->with([
                'values.localization',
                'localization'
            ])->get();
        }
    }

    /**
     * Установка фильтра по цене
     *
     * @param null $price_filter
     * @return $this
     */
    protected function setPrice($price_filter = null){
        $categories = new Category();
        if(!empty($this->category)){
            $this->min_price = $this->flooring($categories->min_price($this->category->id), 100);
            $this->max_price = $this->ceiling($categories->max_price($this->category->id), 100);

            $price = [];
            $price[0] = empty($this->params['price_min']) ? 0 : $this->params['price_min'];
            $price[1] = empty($this->params['price_max']) || $this->params['price_max'] > $this->max_price ? $this->max_price : $this->params['price_max'];

            if(!empty($this->params['price'])){
                $price = explode('-', $this->params['price']);
            }

            if(!empty($price_filter) && strpos($price_filter, 'price-') !== false){
                $price = explode('-', str_replace('price-', '', $price_filter));
            }

            $this->price = $price;
//            $this->setPriceRanges();
        }

        return $this;
    }

    /**
     * Ручная установка минимальной цены
     *
     * @param $price
     * @return $this
     */
    public function setMinPrice($price){
        $this->price[0] = $price;
        if(!isset($price[1]))
            $price[1] = empty($this->params['price_max']) || $this->params['price_max'] > $this->max_price ? $this->max_price : $this->params['price_max'];

        return $this;
    }

    /**
     * Ручная установка максимальной цены
     *
     * @param $price
     * @return $this
     */
    public function setMaxPrice($price){
        $this->price[1] = $price;
        if(!isset($price[0]))
            $price[0] = empty($this->params['price_min']) ? 0 : $this->params['price_min'];

        return $this;
    }

    protected function ceiling($number, $significance = 1){
        return ( is_numeric($number) && is_numeric($significance) ) ? (ceil($number/$significance)*$significance) : false;
    }

    protected function flooring($number, $significance = 1){
        return ( is_numeric($number) && is_numeric($significance) ) ? (floor($number/$significance)*$significance) : false;
    }

    /**
     * Установка поискового запроса
     *
     * @param $text
     */
    public function setSearchText($text){
        $this->search_text = $text;
    }

	/**
	 * Получение коллекции отфильтрованных товаров
	 *
	 * @param array $current_sort
	 * @param int $take
	 * @param int $page
	 *
	 * @return null
	 */
    public function getProducts($current_sort = ['sort_priority', 'asc'], $take = 20, $page = 1){
	    if(empty($this->products)){
	    	if($this->enable_redis){
			    $this->products = $this->getRedisProducts($current_sort, $take)->appends(['page' => $page]);
		    }else{
			    $this->products = $this->getDatabaseProducts($current_sort, $take)->appends(['page' => $page]);
		    }
	    }

	    if($current_sort != ['sort_priority', 'asc']){
            $this->products->appends(['order' => implode('-', $current_sort)]);
        }

	    return $this->products;
    }

    /**
     * Товары, семантически близкие к поисковому запросу (по эмбеддингам).
     * Короткий таймаут и try/catch — при недоступности/задержке AI-сервиса
     * поиск должен продолжать работать по ключевым словам, как и раньше.
     *
     * @param string $search_text
     * @return array
     */
    private function getSemanticSearchIds($search_text){
        try {
            return Product::semanticSearch($search_text, 15, true, 4)->pluck('id')->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Semantic search augmentation failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Разбор bitmap
     *
     * @param $bitmap
     * @return array
     */
    private function bitmap_ids($bitmap){
        $bytes = unpack('C*', $bitmap);
        $bin = join(array_map(function($byte){
            return sprintf("%08b", $byte);
        }, $bytes));
        return array_keys(str_split($bin), 1);
    }

	/**
	 * Товары из базы данных
	 *
	 * @param $sort
	 * @param int $take
	 * @return mixed
	 */
    private function getDatabaseProducts($sort, $take = 36){
	    $page = $this->page;
	    $orderBy = $sort[0];
	    $route = $sort[1];

	    Paginator::currentPageResolver(function () use ($page) {
		    return $page;
	    });

	    $products = Product::select('products.*');
	    $products->where('visible', 1);
        if($this->category->id == 2){
            $products->whereDoesntHave('categories', function ($query) {
                $query->where('categories.id', 1);
            });
        }else{
            $categories = $this->getCurrentCategoriesWithChildrenIds();
            if(!empty($categories)){
                $this->categories_with_children = $categories;
                $products->join('product_categories AS cat', 'products.id', '=', 'cat.product_id');
                $products->whereIn('cat.category_id', $categories);
            }
        }

	    if(!empty($this->filtered)){
		    foreach($this->filtered as $att_id => $values){
			    $products->join('product_attributes AS attr' . $att_id, 'products.id', '=', 'attr' . $att_id . '.product_id');
			    $products->where('attr' . $att_id . '.attribute_id', $att_id);
			    $products->where(function($query) use($att_id, $values){
				    $query->orWhereIn('attr' . $att_id . '.attribute_value_id', $values);
			    });
		    }
	    }

        $search_text = $this->search_text;
        if(!empty($search_text)) {
            $search = explode(' ', $search_text);
            if (count($search) == 1) {
                $search_ids = array_unique(array_merge(Localization::select('localizable_id')->where('localizable_type', 'Products')
                    ->where('value', 'like', '%' . $search_text . '%')
                    ->groupBy('localizable_id')
                    ->get()->pluck('localizable_id')->toArray(),
                    Localization::select('product_attributes.product_id')->join('product_attributes', 'localization.localizable_id', '=', 'product_attributes.attribute_value_id')
                        ->where('localizable_type', 'Values')
                        ->where('value', 'like', '%' . $search_text . '%')
                        ->groupBy('product_attributes.product_id')
                        ->get()->pluck('product_id')->toArray()
                ));
                $products->where(function($query) use ($search_ids, $search_text){
                    $query->whereIn('products.id', $search_ids)
                    ->orWhere('products.name', 'like', '%' . $search_text . '%');
                });
            } else {
                $search_ids = array_unique(array_merge(Localization::select('localizable_id')->where('localizable_type', 'Products')
                    ->where(function ($query) use ($search) {
                        foreach ($search as $s) {
                            $query->where('value', 'like', '%' . $s . '%');
                        }
                    })
                    ->groupBy('localizable_id')
                    ->get()->pluck('localizable_id')->toArray(),
                    Localization::select('product_attributes.product_id')->join('product_attributes', 'localization.localizable_id', '=', 'product_attributes.attribute_value_id')
                        ->where('localizable_type', 'Values')
                        ->where(function ($query) use ($search) {
                            foreach ($search as $s) {
                                $query->where('value', 'like', '%' . $s . '%');
                            }
                        })
                        ->groupBy('product_attributes.product_id')
                        ->get()->pluck('product_id')->toArray()
                ));
                $products->where(function($query) use ($search_ids, $search_text){
                    $query->whereIn('products.id', $search_ids)
                        ->orWhere('products.name', 'like', '%' . $search_text . '%');
                });
            }
        }

	    if(!empty($this->sale_id)){
            $products->join('sale_products', 'products.id', '=', 'sale_products.product_id');
            $products->where('sale_products.sale_id', $this->sale_id);
        }

//        $products->orderByRaw("if(stock > 0, 1, 0) desc");
	    $products->orderBy($orderBy, $route);
        $products->groupBy('products.id');
        $products = $this->addRelationsToCollection($products);

	    return $products->paginate($take);
    }

    /**
     * Товары из Redis
     *
     * @param $sort
     * @param int $take
     * @return mixed
     */
    private function getRedisProducts($sort, $take = 20){
        $page = $this->page;

        $results = ["product_visible"];

        // Фильтрация по категориям
        if(empty($this->categories)){
            if(!empty($this->category)){
                $results[] = "category_".$this->category->id;
            }
        }else{
            $key = "result_categories_".implode('_', collect($this->categories)->pluck('id')->toArray());
            $params = ["or", $key];
            $results[] = $key;
            foreach($this->categories as $cat){
                $params[] = "category_$cat->id";
            }
            Redis::command('bitop', $params);
        }

        // Фильтрация по атрибутам
        if(!empty($this->filtered)){
            foreach($this->filtered as $att_id => $values){
                $key = "result_attr_$att_id"."_val_".implode('_', $values);
                $params = ["or", $key];
                $results[] = $key;
                foreach($values as $val_id){
                    $params[] = "attribute_$val_id";
                }
                Redis::command('bitop', $params);
            }
        }

        // Фильтрация по акциям
        if(!empty($this->sale_id)){
            $results[] = "sale_".$this->sale_id;
        }

        // Фильтрация по наличию
        if($this->stock != $this->not_stock){
            if($this->stock === 1){
                $results[] = "product_stock";
            }
            if($this->not_stock === 1){
                $results[] = "product_not_stock";
            }
        }

        // Фильтрация по цене
//        if(!empty($this->price) && count($this->price) == 2 && ($this->price[0] != $this->min_price || $this->price[1] != $this->max_price)){
//            $params = ["prices"];
//            foreach($this->price as $val){
//                $params[] = (int)$val*100;
//            }
//            $ids = Redis::command('zrangebyscore', $params);
//            sort($ids);
//
//            $key = "price_".implode('_', $this->price);
//            $results[] = $key;
//            $params = [$key];
//            foreach($ids as $id){
//                $params[] = 'SET';
//                $params[] = 'u1';
//                $params[] = $id;
//                $params[] = 1;
//            }
//
//            Redis::command('del', [$params[0]]);
//            Redis::command('bitfield', $params);
//        }

        // Фильтрация наличию акционной цены
        if($this->only_sales){
            $results[] = "product_sale";
        }

        // Сохранение всех фильтраций для генерации фильтров
        $this->redis_keys = $results;

        $params = ["and", "result"];
        foreach($results as $result){
            $params[] = $result;
        }
        Redis::command('bitop', $params);
        $bitmap = Redis::get('result');
        $ids = $this->bitmap_ids($bitmap);

        // Поисковый запрос
        $search_text = $this->search_text;
        if(!empty($search_text)){
            $search = explode(' ', $search_text);

            if (count($search) == 1){
                $search_ids = array_unique(array_merge(Localization::select('localizable_id')->where('localizable_type', 'Products')
                    ->where('value', 'like', '%' . $search_text . '%')
                    ->groupBy('localizable_id')
                    ->get()->pluck('localizable_id')->toArray(),
                    Localization::select('product_attributes.product_id')->join('product_attributes', 'localization.localizable_id', '=', 'product_attributes.attribute_value_id')
                        ->where('localizable_type', 'Values')
                        ->where('value', 'like', '%' . $search_text . '%')
                        ->groupBy('product_attributes.product_id')
                        ->get()->pluck('product_id')->toArray(),
                    Product::select('id')
                        ->where('name', 'like', '%' . $search_text . '%')
                        ->orWhere('sku', 'like', '%' . $search_text . '%')
                        ->get()->pluck('id')->toArray()
                ));
            } else {
                $search_ids = array_unique(array_merge(Localization::select('localizable_id')->where('localizable_type', 'Products')
                    ->where(function ($query) use ($search) {
                        foreach ($search as $s) {
                            $query->where('value', 'like', '%' . $s . '%');
                        }
                    })
                    ->groupBy('localizable_id')
                    ->get()->pluck('localizable_id')->toArray(),
                    Localization::select('product_attributes.product_id')->join('product_attributes', 'localization.localizable_id', '=', 'product_attributes.attribute_value_id')
                        ->where('localizable_type', 'Values')
                        ->where(function ($query) use ($search) {
                            foreach ($search as $s) {
                                $query->where('value', 'like', '%' . $s . '%');
                            }
                        })
                        ->groupBy('product_attributes.product_id')
                        ->get()->pluck('product_id')->toArray(),
                    Product::select('id')
                        ->where('name', 'like', '%' . $search_text . '%')
                        ->get()->pluck('id')->toArray()
                ));
            }

            // Семантическое расширение поиска — только когда буквальных совпадений мало:
            // так самые частые запросы не платят задержку похода в AI-сервис за эмбеддингом,
            // а помощь семантики приходит именно там, где keyword-поиск проседает
            if(count($search_ids) < 5){
                $semantic_ids = $this->getSemanticSearchIds($search_text);
                if(!empty($semantic_ids)){
                    $search_ids = array_unique(array_merge($search_ids, $semantic_ids));
                }
            }

            if(!empty($search_ids))
                $ids = array_intersect($ids, $search_ids);
            else
                $ids = [];
        }

	    $orderBy = $sort[0];
	    $route = $sort[1];

	    if(empty($take)){
            $offset = 0;
            $take = count($ids);
        }else
	        $offset = $take * ($page - 1);

        $offset -= $this->banners_displayed_count;

	    if(empty($take))
            $take = 36;

        $take -= $this->banners_count;

        $stock_order = [];

        $stock_order[0] = array_intersect($this->bitmap_ids(Redis::get('product_stock')), $ids);
        $stock_order[1] = array_intersect($this->bitmap_ids(Redis::get('product_expected')), $ids);
        $stock_order[2] = array_intersect($this->bitmap_ids(Redis::get('product_under_the_order')), $ids);
        $stock_order[3] = array_intersect($this->bitmap_ids(Redis::get('product_not_stock')), $ids);

        // Сортировка
        if($orderBy == 'price'){
            if($route == 'asc')
                $all_sorted = Redis::command('zrange', ['prices', '0', '-1']);
            else
                $all_sorted = Redis::command('zrevrange', ['prices', '0', '-1']);

//            $sorted = array_intersect($all_sorted, $ids);
            $sorted = [];
            foreach($stock_order as $stock_type){
                $sorted = array_merge($sorted, array_intersect($all_sorted, $stock_type));
            }
        }elseif($orderBy == 'name'){
            $all_sorted = Redis::command('zrange', ['names', '0', '-1']);
            if($route == 'desc')
                $all_sorted = array_reverse($all_sorted);

            $sorted = [];
            foreach($stock_order as $stock_type){
                $sorted = array_merge($sorted, array_intersect($all_sorted, $stock_type));
            }
        }elseif($orderBy == 'rating'){
            if($route == 'asc')
                $all_sorted = Redis::command('zrange', ['ratings', '0', '-1']);
            else
                $all_sorted = Redis::command('zrevrange', ['ratings', '0', '-1']);

            $sorted = [];
            foreach($stock_order as $stock_type){
                $sorted = array_merge($sorted, array_intersect($all_sorted, $stock_type));
            }
        }elseif($orderBy == 'id'){
            arsort($ids);
            $sorted = $ids;
        }elseif($orderBy == 'priority'){
            if($route == 'asc')
                $all_sorted = Redis::command('zrange', ['sort_priority', '0', '-1']);
            else
                $all_sorted = Redis::command('zrevrange', ['sort_priority', '0', '-1']);

            $sorted = [];
            foreach($stock_order as $stock_type){
                $sorted = array_merge($sorted, array_intersect($all_sorted, $stock_type));
            }
        }else{
//            arsort($ids);
//            $sorted = $ids;
            $sorted = [];
            foreach($stock_order as $stock_type){
                arsort($stock_type);
                $sorted = array_merge($sorted, $stock_type);
            }
        }

//        if($this->stock == $this->not_stock){
//            $bitmap = Redis::get('product_not_stock');
//            $not_in_stock_ids = $this->bitmap_ids($bitmap);
//            $in_stock = [];
//            $not_in_stock = [];
//            foreach($sorted as $id){
//                if(in_array($id, $not_in_stock_ids)){
//                    $not_in_stock[] = $id;
//                }else{
//                    $in_stock[] = $id;
//                }
//            }
//            $sorted = array_merge($in_stock, $not_in_stock);
//        }

        $current_page_ids = array_slice($sorted, $offset, $take);

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $products = Product::select('products.*');
        $products->whereIn('products.id', $current_page_ids);
        $products = $this->addRelationsToCollection($products)->get();

        $collection = [];
        foreach($current_page_ids as $id){
            $collection[] = $products->first(function($value, $key) use($id){
                return $value->id == $id;
            });
        }

        return new LengthAwarePaginator($collection, count($ids), $take, $page, ['path' => request()->ajax() ? $this->getCurrentLink(implode('-', $sort), request()->view) : request()->url(), 'pageName' => 'page']);

//      $products->whereIn('products.id', $ids);
//      $products->orderByRaw("if(stock > 0, 1, 0) desc");
//      $products->orderBy($orderBy, $route);
//	    return new LengthAwarePaginator($products->take($take)->offset($offset)->get(), count($ids), $take, $page, ['path' => request()->url(), 'pageName' => 'page']);
    }

    private function addRelationsToCollection($collection){
        $locale = App::getLocale();
        $user = Sentinel::check();

        $relations = ['image',
        'seo' => function($query){
            $query->select(['url', 'seotable_id', 'seotable_type']);
        },
        'localization' => function($query) use($locale){
            $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
        },
        'wishlist' => function($query) use ($user){
            if($user)
                $query->where('user_id', $user->id)->count();
            else
                $query->where('user_id', 0)->count();
        },
        'categories' => function($query) use($locale){
            $query->orderBy('parent_id', 'asc')
                ->with(['localization' => function($query) use($locale){
                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                }]);
        },
        'values' => function($query) use($locale){
            $query
                ->whereIn('product_attributes.attribute_id', [1, 2])
                ->with(['localization' => function($query) use($locale){
                    $query->select(['field', 'language', 'value', 'localizable_type', 'localizable_id'])->where('language', $locale);
                }]);
        },
        'actual_sales',
        'gallery',
//        'variations.attribute_values',
        ];

        if (!module_active('wishlist')) {
            unset($relations['wishlist']);
        }

        $collection->with($relations);
//        ->withCount('reviews');

        return $collection;
    }

    /**
     * Получение фильтров для вывода
     *
     * @param bool $with_url
     * @return array
     */
    public function getFilterAttributes($with_url = false){
    	if(empty($this->attributes))
	        $this->setFilterAttributes($with_url);

    	return $this->attributes;
    }

    /**
     * Получение ценовых диапазонов
     *
     * @return Filter|array
     */
    public function getPriceRanges(){
        if(empty($this->price_ranges)){
            $this->setPriceRanges();
        }

        return $this->price_ranges;
    }

    /**
     * Ценовые диапазоны для слайдера цен
     *
     * @return array
     */
    public function getPriceSlider(){
        return array_merge([$this->min_price, $this->max_price], $this->price);
    }

    /**
     * Настройка фильтров для вывода
     *
     * @param bool $with_url
     * @return $this|array
     */
	protected function setFilterAttributes($with_url = false){
//        $total = $this->products->total();
        $filter = $this->filtered;

        $attrs = [];
        if(!empty($this->product_attributes)){
	        foreach($this->product_attributes as $key => $attribute){
	            $values = [];

	            if(in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio'])){
                    $attribute->load('values.image');
                }

	            // Выбранные атрибуты
	            if(isset($filter[$attribute->id])){
	                foreach($attribute->values as $i => $attribute_value){
                        if($this->enable_redis){
//                            $count = $this->get_products_count([$attribute->id => [$attribute_value->id]]) - $total;
                            $count = $this->get_products_count([$attribute->id => [$attribute_value->id]]);
                        }else{
                            $attr_filter = $filter;
                            $attr_filter[$attribute->id] = [$attribute_value->id];
                            $count = $this->get_products_count($attr_filter);
                        }
	                    //if($count > 0){
	                        $values[$attribute_value->id] = [
	                            'name' => $attribute_value->name,
	                            'value' => $attribute_value->value,
	                            'checked' => in_array($attribute_value->id, $filter[$attribute->id]),
	                            'count' => $count,
                                'image' => $attribute->id == 3 ? $attribute_value->image : null
	                        ];
	                        if($with_url)
                                $values[$attribute_value->id]['url'] = $this->getFilterUrl($attribute_value);
	                    //}
	                }
	            // Не выбранные атрибуты
	            }else{
	                foreach($attribute->values as $i => $attribute_value){
                        if($this->enable_redis){
//                            $count = $this->get_products_count([$attribute->id => [$attribute_value->id]]) - $total;
                            $count = $this->get_products_count([$attribute->id => [$attribute_value->id]]);
                        }else{
                            $attr_filter = $filter + [$attribute->id => [$attribute_value->id]];
                            $count = $this->get_products_count($attr_filter);
                        }
	                    //if($count > 0){
	                        $values[$attribute_value->id] = [
	                            'name' => $attribute_value->name,
	                            'value' => $attribute_value->value,
	                            'checked' => false,
	                            'count' => $count,
                                'image' => $attribute->id == 3 ? $attribute_value->image : null
	                        ];
                            if($with_url)
                                $values[$attribute_value->id]['url'] = $this->getFilterUrl($attribute_value);
	                    //}
	                }
	            }

	            if(count($values) > 0 || isset($filter[$attribute->id])){
	                $attrs[$attribute->id] = [
	                    'name' => $attribute->name,
                        'unit' => $attribute->unit,
	                    'slug' => $attribute->slug,
		                'active' => isset($filter[$attribute->id]),
	                    'values' => $values
	                ];
	            }
	        }
        }

	    $this->attributes = ['attributes' => $attrs];
//        $categories_filter = $this->getCategoriesFilter();
//        if(!empty($categories_filter['values']))
//            $this->attributes['categories'] = $categories_filter;

//        $this->attributes['stock'] = [
//            'active' => $this->stock || $this->not_stock,
//            'values' => [
//                '1' => $this->getProductParamFilter('stock', __('В наличии')),
//                '0' => $this->getProductParamFilter('not_stock', __('Нет на складе')),
//            ]
//        ];
//        $this->attributes['stock']['values'][1]['count'] -= $total;
//        $this->attributes['stock']['values'][0]['count'] -= $total;

//        if($this->stock)
//            $this->attributes['stock']['values'][0]['count'] = $this->attributes['stock']['values'][1]['count'];
//
//        if($this->not_stock)
//            $this->attributes['stock']['values'][1]['count'] = $this->attributes['stock']['values'][0]['count'];

        $this->attributes['is_sale'] = [
            'active' => $this->only_sales
        ];

        return $this;
    }

    /**
     * Генерация ЧПУ URL для фильтров
     *
     * @param $attribute_value
     * @param null $category
     * @return string
     */
    protected function getFilterUrl($attribute_value = null, $category = null){
	    $alias = !empty($this->category) ? $this->category->slug : 'catalog';
	    $filter = $this->filtered;
	    $product_attributes = $this->product_attributes;

        $categories = [];
        if(!empty($this->search_text)) {
            $url = '/search';
        }elseif(!empty($this->sale_id) && !empty($sale = Sale::find($this->sale_id))){
            $url = $sale->seo->url;
        }elseif($this->allow_multi_categories){
            if(!empty($this->categories)){
                foreach($this->categories as $cat){
                    $categories[$cat->id] = $cat->slug;
                }
            }else{
                $categories[$this->root_id] = Category::find($this->root_id)->slug;
            }

            if(!empty($category)){
                if(isset($categories[$category->id])){
                    unset($categories[$category->id]);
                }else{
                    $categories[$category->id] = $category->slug;
                }
            }

            if(count($categories) > 1){
                $url = '/'.$alias.'/categories_'.implode('_', $categories);
            }else{
                $url = '/'.$alias;
            }
        }else{
            if(!empty($category)){
                $url = $category->link();
            }elseif(!empty($this->category)){
                $url = $this->category->link();
            }else{
                $url = '/catalog';
            }
        }

        $search = '';
	    if(!empty($attribute_value)) {
		    if ( isset( $filter[ $attribute_value->attribute_id ] ) && in_array( $attribute_value->id, $filter[ $attribute_value->attribute_id ] ) ) {
			    unset( $filter[ $attribute_value->attribute_id ][ array_search( $attribute_value->id, $filter[ $attribute_value->attribute_id ] ) ] );
		    } else {
			    $filter[ $attribute_value->attribute_id ][] = $attribute_value->id;
		    }
	    }

        if($this->only_sales){
            $url .= '/sale';
        }

        asort($filter);
        foreach($filter as $attr_id => $values){
            if(!empty($values)){
                asort($values);
                $attr = $product_attributes->find($attr_id);
                if(!empty($attr)){
                    if(!empty($search)){
                        $search .= '/';
                    }
                    $search .= str_replace(array('#', '_', '?'), '', $attr->slug);
                    foreach ($values as $value_id){
                        $value = $attr->values->find($value_id);
                        $search .= '_'.str_replace(array('#', '_', '?'), '', $value->value);
                    }
                }
            }
        }

        if(!empty($search))
            $url .= '/'.$search;

        if($this->stock){
            $url .= '/stock-1';
        }elseif($this->not_stock){
            $url .= '/stock-0';
        }

        if(!empty($this->price) && count($this->price) == 2 && ($this->price[0] > $this->min_price || $this->price[1] < $this->max_price))
            $url .= '/price-'.$this->price[0].'-'.$this->price[1];

        return $url;
    }

    /**
     * Генерация ценовых диапазонов
     *
     * @return $this
     */
    protected function setPriceRanges(){
        if(empty($this->category)){
            return $this;
        }

        $min_price = $this->min_price;
        $max_price = $this->max_price;
        $price = $this->price;
        $category_id = $this->category->id;
        $attr_filter = $this->filtered;

        $values = [];
        $categories = new Category();

        $step = ($max_price - $min_price) / 6;
        $round = 8;
        while (round($step, $round) > 0){
            $round--;
        }
        $step = round($step, $round+1);

        for($i=1; $i<6; $i++){
            if($i == 1){
                $size = '< ' . ($min_price + $step * $i);
                $fprice = [0, $min_price + $step * $i];
                $id = '0-' . ($min_price + $step * $i);
            }elseif($i == 5){
                $size = '> ' . ($min_price + $step * ($i-1));
                $fprice = [$min_price + $step * ($i-1), $max_price];
                $id = ($min_price + $step * ($i-1)) . '-' . $max_price;
            }else{
                $size = ($min_price + $step * ($i-1)) . ' - ' . ($min_price + $step * $i);
                $fprice = [$min_price + $step * ($i-1), ($min_price + $step * $i)];
                $id = str_replace(' ', '', $size);
            }

            if($price[0] > 0 || $price[1] < $max_price){
                if($fprice == $price){
                    $values[$id] = [
                        'name' => $size,
                        'checked' => true,
                        'slug' => $id,
                        'url' => $this->getFilterUrl(null, null)
                    ];
                }
            }else{
                $count = $categories->get_products_count($category_id, $attr_filter, $fprice, 1);
                if($count){
                    $values[$id] = [
                        'name' => $size,
                        'checked' => false,
                        'count' => $count,
                        'slug' => $id,
                        'url' => $this->getFilterUrl(null, null)
//                        'url' => $this->getFilterUrl(null, ['slug' => $id])
                    ];
                }
            }
        }

        $this->price_ranges = $values;

        return $this;
    }

    /**
     * Установка структурированного фильтра
     *
     * @return $this
     */
    protected function setFiltered(){
        $filters = $this->path;
        $filter = [];

        if(!empty($filters)){
            $filter = $this->prepared_filters($filters);
            $filter = $this->parseValuesIds($filter);
        }
        $this->filtered = $filter;

        return $this;
    }

	/**
	 * Получение списка выбранных фильтров
	 *
	 * @return array
	 */
	public function getFiltered(){
    	return $this->filtered;
	}

    /**
     * Получение ID фильтров по их слагу
     *
     * @param $filter
     * @return array
     */
    protected function parseValuesIds($filter){
        $new_filter = [];
        $attr = new Attribute();
        foreach ($filter as $attr_slug => $values){
            if($attr_slug != 'price') {
                $attribute = $attr->where('slug', $attr_slug)->first();
                if ($attribute !== null) {
                    foreach ($values as $value_slug) {
                        $val = $attribute->values()->where('value', $value_slug)->first();
                        if ($val !== null) {
                            $new_filter[$attribute->id][] = $val->id;
                        }
                    }
                }
            }
        }
        return $new_filter;
    }

    /**
     * Парсинг фильтров
     *
     * @param string $string
     * @return array
     */
    protected function prepared_filters($string = ''){
        $data = explode('_', $string);

        $filters = [];
        foreach ($data as $filter){
            $filter_data = explode('-', $filter);
            if(count($filter_data) >= 2) {
                $key = $filter_data[0];
                array_splice($filter_data, 0, 1);
                $filters[$key] = $filter_data;
            }
        }

        return $filters;
    }

    /**
     * Получение выбранного ценового интервала
     *
     * @return mixed|null
     */
    public function getCurrentPriceRange(){
        if(!empty($this->price_ranges)){
            foreach ($this->price_ranges as $range){
                if($range['checked']){
                    return $range;
                }
            }
        }

        return null;
    }

    private function get_products_count($filter){
	    if($this->enable_redis){
		    $results = $this->redis_keys;

		    if(!empty($filter)){
			    foreach($filter as $att_id => $values){
			        // Фильтр выбран
				    if(isset($this->filtered[$att_id])){
					    $val = "result_attr_$att_id"."_val_".implode('_', $this->filtered[$att_id]);
					    if(($key = array_search($val, $results)) !== false){
						    unset($results[$key]);
					    }

//					    $checked_values = $this->filtered[$att_id];
//					    foreach($values as $val_id) {
//						    if(in_array($val_id, $checked_values)){
//							    if(($key = array_search($val_id, $checked_values)) !== false){
//								    unset($checked_values[$key]);
//							    }
//						    }else{
//							    $checked_values[] = $val_id;
//						    }
//					    }
//
//					    unset($key);
//					    if(!empty($checked_values)){
//						    $key = "result_attr_$att_id"."_val_".implode('_', $checked_values);
//                            $values = $checked_values;
//					    }
                        $key = "result_attr_$att_id"."_val_".implode('_', $values);
				    }else{ // Фильтр не выбран
					    $key = "result_attr_$att_id"."_val_".implode('_', $values);
				    }

				    // Добавляем фильтр
				    if(!empty($key) && !in_array($key, $results)){
					    $params = ["or", $key];
					    foreach($values as $val_id){
						    $params[] = "attribute_$val_id";
					    }

					    if(count($params) > 3){
						    Redis::command('bitop', $params);
						    $results[] = $key;
					    }elseif(!empty($params[2])){
						    $results[] = $params[2];
					    }
				    }
			    }
		    }

		    $params = ["and", "count"];
		    foreach($results as $result){
			    $params[] = $result;
		    }
		    Redis::command('bitop', $params);
		    $count = Redis::bitcount('count');
	    }else{
		    $count = $this->getProductsCount($this->getCurrentCategoriesWithChildrenIds(), $filter);
	    }

        return $count;
    }

    private function getCategoriesFilter(){
        $filter = [
            'name' => __('Ctegory'),
            'slug' => 'categories',
	        'active' => false,
            'values' => []
        ];
	    $selected_ids = [];

	    if($this->allow_multi_categories && !empty($this->categories)){
		    $selected_ids = collect($this->categories)->pluck('id')->toArray();
	    }else{
		    $selected_ids[] = $this->category->id;
	    }

	    if($this->enable_redis){
		    foreach(Category::where('parent_id', 1)->orderBy('sort_order')->with('localization')->get() as $category){
			    $results = $this->redis_keys;
			    if(in_array($category->id, $selected_ids)){
				    if(($k = array_search("category_$category->id", $results)) !== false){
					    unset($results[$k]);
				    }
			    }else{
				    $results[] = "category_$category->id";
			    }

			    $params = ["and", "count"];
			    foreach($results as $result){
				    $params[] = $result;
			    }

			    Redis::command('bitop', $params);
			    $count = Redis::bitcount('count');

			    $checked = in_array($category->id, $selected_ids);
			    if($checked){
				    $filter['active'] = true;
			    }

			    $filter['values'][$category->id] = [
				    'name' => $category->name,
				    'value' => $category->slug,
				    'checked' => $checked,
				    'count' => $count,
                    'url' => $this->getFilterUrl(null, $category)
			    ];
		    }
	    }else{
	        if(!empty($this->category)){
                foreach($this->category->children()->orderBy('sort_order')->with('localization')->get() as $category){
                    $count = $this->getProductsCount($this->getCurrentCategoriesWithChildrenIds($category->id), $this->filtered);
                    $checked = in_array($category->id, $selected_ids);
                    if($checked){
                        $filter['active'] = true;
                    }

                    $filter['values'][$category->id] = [
                        'name' => $category->name,
                        'value' => $category->slug,
                        'checked' => $checked,
                        'count' => $count,
                        'url' => $this->getFilterUrl(null, $category)
                    ];
                }
            }
	    }

        return $filter;
    }

    private function getProductParamFilter($key, $name){
	    if($this->enable_redis){
		    if($this->$key){
			    $results = $this->redis_keys;
			    if(($k = array_search("product_$key", $results)) !== false){
				    unset($results[$k]);
			    }
			    $checked = true;
		    }else{
			    $results = $this->redis_keys;
			    $results[] = "product_$key";
			    $checked = false;
		    }

		    $params = ["and", "count"];
		    foreach($results as $result){
			    $params[] = $result;
		    }

		    Redis::command('bitop', $params);
		    $count = Redis::bitcount('count');
	    }else{
		    if($this->$key){
			    $checked = true;
		    }else{
			    $checked = false;
		    }
		    $count = 0;
	    }

        return [
            'name' => $name,
            'value' => $key,
            'checked' => $checked,
            'count' => $count,
        ];
    }

	/**
	 * Получение ID выбранных категорий, включая дочерние
	 *
	 * @param null $toggle
	 *
	 * @return array
	 */
    private function getCurrentCategoriesWithChildrenIds($toggle = null){
	    if(empty($this->categories_with_children) || !empty($toggle)){
		    $categories = new Category();

		    if(empty($this->categories) && empty($toggle)) {
			    $ids = array_merge([$this->category->id], $categories->getChildrenCategories($this->category->id));
		    }elseif(empty($this->categories) && !empty($toggle)){
		    	if($this->category->id == $toggle){
		    		return [];
			    }else{
				    $ids = array_merge([$this->category->id], $categories->getChildrenCategories($this->category->id));
				    $ids = array_merge($ids, [$toggle], $categories->getChildrenCategories($toggle));
			    }
		    }else{
		    	if(empty($toggle)){
				    $ids = [];
				    foreach($this->categories as $category){
					    $ids = array_merge($ids, [$category->id], $categories->getChildrenCategories($category->id));
				    }
			    }else{
				    $ids = [];
				    $add_toggle = true;
				    foreach($this->categories as $category){
				    	if($category->id == $toggle){
						    $add_toggle = false;
				    		continue;
					    }

					    $children = $categories->getChildrenCategories($category->id);
				    	if(in_array($toggle, $children)){
						    $add_toggle = false;
						    if(($key = array_search($toggle,$children)) !== false){
							    unset($children[$key]);
						    }
						    $ids = array_merge($ids, $children);
					    }else{
						    $ids = array_merge($ids, [$category->id], $children);
					    }
				    }

				    if($add_toggle){
					    $ids = array_merge($ids, [$toggle], $categories->getChildrenCategories($toggle));
				    }
			    }
		    }

		    if(empty($toggle))
		        $this->categories_with_children = $ids;
	    }else{
		    $ids = $this->categories_with_children;
	    }

	    return $ids;
    }

    private function getProductsCount($categories, $filter){
	    $hash = md5(serialize($categories).serialize($filter));

	    $count = Cache::remember($hash, 1440, function () use ($categories, $filter){
		    $products = Product::select('products.*');
		    $products->where('visible', 1);

		    if(!empty($categories)){
			    $products->join('product_categories AS cat', 'products.id', '=', 'cat.product_id');
			    $products->whereIn('cat.category_id', $categories);
		    }

		    if(!empty($filter)){
			    foreach($filter as $key => $attribute){
				    $products->join('product_attributes AS attr' . $key, 'products.id', '=', 'attr' . $key . '.product_id');
				    $products->where('attr' . $key . '.attribute_id', $key);
				    $products->where(function($query) use($attribute, $key){
					    foreach($attribute as $attribute_value){
						    $query->orWhere('attr' . $key . '.attribute_value_id', $attribute_value);
					    }
				    });
			    }
		    }

		    if(!empty($price)){
			    $products->whereBetween('products.price', $price);
		    }

		    $products->groupBy('products.id');

		    if(!empty($limit)){
			    $products->limit($limit);
		    }

		    return $products->count();
	    });

	    return $count;
    }

    public function getCurrentCategory(){
        return $this->category;
    }

    public function getCurrentLink($order = null, $view = null, $limit = null){
        $link = $this->getFilterUrl();
        if(!empty($this->page) && $this->page > 1)
            $link .= '/page-'.$this->page;

        $get = [];

        if(!empty($this->search_text))
            $get['text'] = $this->search_text;

        if(!empty($order) && $order !== 'priority-asc')
            $get['order'] = $order;

//        if(!empty($view) && $view !== 'grid')
//            $get['view'] = $view;
//
//        if(!empty($limit) && $limit != 36)
//            $get['limit'] = $limit;

        if(!empty($get))
            $link .= '?'.http_build_query($get);

        return $link;
    }

    public function getSelectedFilters(){
        $selected = [];

        if(!empty($this->attributes['stock']['active'])){
            foreach($this->attributes['stock']['values'] as $id => $value){
                if($value['checked']){
                    $selected[] = [
                        'type' => 'stock',
                        'name' => 'Stock: ' . $value['name'],
                        'id' => $id
                    ];
                }
            }
        }

        if(!empty($this->attributes['attributes'])){
            foreach($this->attributes['attributes'] as $attribute){
                if(!empty($attribute['active'])){
                    foreach($attribute['values'] as $id => $value){
                        if($value['checked']){
                            $selected[] = [
                                'type' => 'attribute',
                                'name' => $attribute['name'] . ': ' . $value['name'].$attribute['unit'],
                                'id' => $id
                            ];
                        }
                    }
                }
            }
        }

        if(!empty($this->price) && count($this->price) == 2 && ($this->price[0] > $this->min_price || $this->price[1] < $this->max_price)){
            $selected[] = [
                'type' => 'price',
                'name' => __('Price').' '.__('from').' '.$this->price[0].' '.__('to').' '.$this->price[1].' £',
                'id' => 0
            ];
        }

        if(!empty($this->attributes['is_sale']['active'])){
            $selected[] = [
                'type' => 'is_sale',
                'name' => 'With promotion',
                'id' => 1
            ];
        }

        return $selected;
    }

    public function getAdditionalCrumb(){
        if(isset($this->attributes['attributes'][2]) && !empty($this->attributes['attributes'][2]['active'])){
            foreach($this->attributes['attributes'][2]['values'] as $id => $value){
                if($value['checked']){
                    return AttributeValue::find($id);
                }
            }
        }

        return null;
    }

    public function getCategoryProductsIds(){
        if(empty($this->category)){
            return [];
        }
        $results = ["product_visible"];
        $results[] = "category_".$this->category->id;
        $results[] = "product_stock";

        // Сохранение всех фильтраций для генерации фильтров
        $this->redis_keys = $results;

        $params = ["and", "result"];
        foreach($results as $result){
            $params[] = $result;
        }
        Redis::command('bitop', $params);
        $bitmap = Redis::get('result');
        $ids = $this->bitmap_ids($bitmap);

        $stock_order = [];

        $stock_order[0] = array_intersect($this->bitmap_ids(Redis::get('product_stock')), $ids);
        $stock_order[1] = array_intersect($this->bitmap_ids(Redis::get('product_expected')), $ids);
        $stock_order[2] = array_intersect($this->bitmap_ids(Redis::get('product_under_the_order')), $ids);
        $stock_order[3] = array_intersect($this->bitmap_ids(Redis::get('product_not_stock')), $ids);

        // Сортировка
        if(!empty($_COOKIE['products_sort'])){
            if($_COOKIE['products_sort'] == 'priority-asc'){
                $all_sorted = Redis::command('zrange', ['sort_priority', '0', '-1']);
            }elseif($_COOKIE['products_sort'] == 'rating-desc'){
                $all_sorted = Redis::command('zrevrange', ['ratings', '0', '-1']);
            }elseif($_COOKIE['products_sort'] == 'price-asc'){
                $all_sorted = Redis::command('zrange', ['prices', '0', '-1']);
            }elseif($_COOKIE['products_sort'] == 'price-desc'){
                $all_sorted = Redis::command('zrevrange', ['prices', '0', '-1']);
            }
        }else{
            $all_sorted = Redis::command('zrange', ['sort_priority', '0', '-1']);
        }

        $sorted = [];
        foreach($stock_order as $stock_type){
            $sorted = array_merge($sorted, array_intersect($all_sorted, $stock_type));
        }

        return $sorted;
    }

    public function getPrevProduct($product_id){
        $sorted = $this->getCategoryProductsIds();
        $current_key = array_search($product_id, $sorted);

        if($current_key && isset($sorted[$current_key-1])){
            return Product::find($sorted[$current_key-1]);
        }

        return null;
    }

    public function getNextProduct($product_id){
        $sorted = $this->getCategoryProductsIds();
        $current_key = array_search($product_id, $sorted);

        if($current_key !== false && isset($sorted[$current_key+1])){
            return Product::find($sorted[$current_key+1]);
        }

        return null;
    }
}
