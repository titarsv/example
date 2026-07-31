<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Category extends Entity
{
    use SoftDeletes, HasLocalizationTrait;

    /**
     * Static cache for tree paths to avoid repeated queries
     */
    private static $treePathCache = [];

    protected $fillable = [
    	'id',
    	'slug',
        'file_id',
        'parent_id',
        'status'
    ];

    protected $localized_fields = [
        'name',
        'body'
    ];

    protected $fields_with_pictures = [
        'body'
    ];

    protected $dates = ['deleted_at'];

    public $entity_type = 'category';
    protected $table = 'categories';

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

    public function products(){
        return $this->belongsToMany('App\Models\Product', 'product_categories', 'category_id', 'product_id');
    }

    public function attributes(){
        return $this->belongsToMany('App\Models\Attribute', 'category_attributes', 'category_id', 'attribute_id');
    }

    public function children(){
        return $this->hasMany('App\Models\Category', 'parent_id', 'id')->with('children');
    }

	public function parent(){
		return $this->belongsTo('App\Models\Category', 'parent_id');
	}

    public function galleries(){
        return $this->morphMany('App\Models\Gallery', 'parent');
    }

    public function gallery(){
        return $this->morphMany('App\Models\Gallery', 'parent')->where('field', 'gallery');
    }

    public function saveGalleries($request){
        $gallery = new Gallery();
        $gallery->saveGalleries($request, $this, ['gallery']);
    }

	// Сео
	public function saveSeo($request){
        $seo_data = $request->only(['canonical', 'robots']);
        if(!empty($request->url)){
            $seo_data['url'] = $request->url;
        }else{
            $name_key = 'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '');
            $seo_data['url'] = '/'.mb_strtolower(Helper::translit($request->$name_key));
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
        if(empty($this->seo)){
            return '';
        }
        return $this->seo->getLinkAttribute();
    }

    public function get_products_count($category_id, $filter, $price = [], $limit = 0){
        $hash = md5($category_id.serialize($filter).serialize($price));

        $count = Cache::remember($hash, 480, function () use (&$category_id, $filter, $price, $limit) {
            $products = Product::select('products.*');

            $products->where('stock', 1);

            if($category_id !== null) {
                $categories = [];
                if(is_array($category_id)){
                    foreach ($category_id as $id){
                        $categories = array_merge($categories, [$id], $this->get_children_categories($id));
                    }
                }else
                    $categories = array_merge([$category_id], $this->get_children_categories($category_id));
                $products->join('product_categories AS cat', 'products.id', '=', 'cat.product_id');
                $products->whereIn('cat.category_id', $categories);
            }

            if (!empty($filter)) {

                foreach ($filter as $key => $attribute) {

                    $products->join('product_attributes AS attr' . $key, 'products.id', '=', 'attr' . $key . '.product_id');
                    $products->where('attr' . $key . '.attribute_id', $key);
                    $products->where(function($query) use($attribute, $key){

                        foreach ($attribute as $attribute_value) {
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

    public function get_children_categories($cat_id){

        if(isset($this->{'children_categories_'.$cat_id})){
            $categories = $this->{'children_categories_'.$cat_id};
        }else{
            $categories = Cache::remember('children_categories_'.$cat_id, 60, function () use (&$cat_id) {
                $children_categories = $this->select('id')->where('parent_id', $cat_id)->get()->toArray();
                $categories = [];
                if(count($children_categories)) {
                    foreach ($children_categories as $cat) {
                        $categories[] = $cat['id'];
                        $categories = array_merge ($categories, $this->get_children_categories($cat['id']));
                    }
                }
                return $categories;
            });

            $this->{'children_categories_'.$cat_id} = $categories;
        }

        return $categories;
    }

    /**
     * Минимальная стоимость товара в категории
     *
     * @param $category_id
     * @return int
     */
    public function min_price($category_id){

        if(isset($this->{'min_price_'.$category_id})){
            $price = $this->{'min_price_'.$category_id};
        }else{
            $price = Cache::remember('min_price_'.$category_id, 60, function () use (&$category_id) {
                $product = Product::select('products.price');
                $categories = array_merge([$category_id], $this->get_children_categories($category_id));
                $product->join('product_categories AS cat', 'products.id', '=', 'cat.product_id');
                $product->whereIn('cat.category_id', $categories)->where('products.visible', 1);
                $result = $product->orderBy('products.price', 'asc')
                    ->first();

                if(is_null($result)){
                    return 0;
                }else{
                    return $result->price;
                }
            });

            $this->{'min_price_'.$category_id} = $price;
        }

        return $price;
    }

    /**
     * Максимальная стоимость товара в категории
     *
     * @param $category_id
     * @return int
     */
    public function max_price($category_id){

        if(isset($this->{'max_price_'.$category_id})){
            $price = $this->{'max_price_'.$category_id};
        }else{
            $price = Cache::remember('max_price_'.$category_id, 60, function () use (&$category_id) {
                $product = Product::select('products.price');
                $categories = array_merge([$category_id], $this->get_children_categories($category_id));
                $product->join('product_categories AS cat', 'products.id', '=', 'cat.product_id');
                $product->whereIn('cat.category_id', $categories)->where('products.visible', 1);
                $result = $product->orderBy('products.price', 'desc')
                    ->first();

                if(is_null($result)){
                    return 0;
                }else{
                    return $result->price;
                }
            });

            $this->{'max_price_'.$category_id} = $price;
        }

        return $price;
    }

    /**
     * Корневые категории
     *
     * @param int $id
     * @return mixed
     */
    public function get_root_categories($id = 2){
	    $locale = app()->getLocale();
        $categories = $this->where('parent_id', $id)
            ->where('status', 1)
	        ->with(['children' => function($query){
		        $query->select('id', 'parent_id', 'file_id', 'slug')
                    ->where('status', 1)
                    ->with('image')
                    ->with(['children' => function($query){
                        $query->select('id', 'parent_id', 'file_id', 'slug')
                            ->where('status', 1)
                            ->with('image');
                    }])
                    ->with('products')
                    ->withCount('children');
	        }])
            ->with('products')
	        ->withCount('children')
            ->get();

        // Collect all category IDs including children
        $allCategoryIds = $this->collectAllCategoryIds($categories);

        // Fetch all localizations in one query
        if (!empty($allCategoryIds)) {
            $localizations = Localization::whereIn('localizable_id', $allCategoryIds)
                ->where('localizable_type', 'Categories')
                ->where('language', $locale)
                ->get()
                ->groupBy('localizable_id');

            // Attach localizations to all categories recursively
            $this->attachLocalizationsToCategories($categories, $localizations);
        }

        return $categories;
    }

    /**
     * Collect all category IDs recursively from categories and their children
     */
    private function collectAllCategoryIds($categories)
    {
        $ids = [];
        foreach ($categories as $category) {
            $ids[] = $category->id;
            if ($category->children->isNotEmpty()) {
                $ids = array_merge($ids, $this->collectAllCategoryIds($category->children));
            }
        }
        return $ids;
    }

    /**
     * Attach localizations to categories recursively
     */
    private function attachLocalizationsToCategories($categories, $localizations)
    {
        foreach ($categories as $category) {
            if (isset($localizations[$category->id])) {
                $category->setRelation('localization', $localizations[$category->id]);
            }

            if ($category->children->isNotEmpty()) {
                $this->attachLocalizationsToCategories($category->children, $localizations);
            }
        }
    }

    /**
     * Дочерние категории
     * @param int $parent_id
     * @return mixed
     */
    public function get_children($parent_id = 0){
        $children = Cache::remember('children_categories_objects_'.$parent_id, 1440, function () use (&$parent_id) {
            if(empty($parent_id))
                $parent_id = $this->id;
            $children = $this->where('parent_id', $parent_id)
                ->orderBy('name', 'DESC')
                ->get();
            return $children;
        });

        return $children;
    }

    /**
     * Наличие дочерних категорий
     * @return bool
     */
    public function hasChildren(){
        if($this->where('parent_id', $this->id)->count()){
            return true;
        }else
            return false;
    }

    /**
     * Получение массива родительских категорий
     * @param string $category
     * @return array
     */
    public function get_parent_categories($category = ''){
        $categories = [];

        if(!empty($category)){
            if(is_int($category)){
                $category = $this->where('id', $category)->first();
            }elseif(is_string($category)){
                $category = $this->where('url_alias', $category)->first();
            }
        }else{
            $category = $this;
        }

        $categories[] = $category;
        if($category->parent_id > 0)
            $categories = array_merge ($categories, $this->get_parent_categories($category->parent_id));

        return $categories;
    }

    public function getTreeNameAttribute(){
        // Use cached tree path to avoid recursive queries
        $treePath = $this->getCachedTreePath();

        $name = '';
        foreach($treePath as $cat){
            if(!empty($name)){
                $name .= ' > ';
            }
            // Use optimized name retrieval to avoid localization queries
            $name .= $this->getOptimizedName($cat);
        }

        return $name;
    }

    /**
     * Get cached tree path to avoid recursive queries
     */
    private function getCachedTreePath(){
        // Cache key for this category's tree path
        $cacheKey = "category_tree_path_{$this->id}";

        // Try to get from cache first
        if (isset(static::$treePathCache[$cacheKey])) {
            return static::$treePathCache[$cacheKey];
        }

        // Get all ancestors in single query using recursive approach
        $path = $this->getAncestorsWithSingleQuery();

        // Cache the result
        static::$treePathCache[$cacheKey] = $path;

        return static::$treePathCache[$cacheKey];
    }

    /**
     * Get all ancestor categories in single query
     */
    private function getAncestorsWithSingleQuery(){
        // Build path array starting from current category
        $path = [];
        $currentId = $this->id;
        $idsToCheck = [$currentId];
        $categories = [];

        // Collect all IDs in the path
        while ($currentId > 0) {
            $idsToCheck[] = $currentId;
            $currentId = $this->getParentIdFromCache($currentId);
        }

        // Load all categories in single query
        if (!empty($idsToCheck)) {
            $categories = static::whereIn('id', $idsToCheck)
                ->orderBy('id')
                ->get()
                ->keyBy('id');
        }

        // Build ordered path from root to current
        $orderedPath = [];
        $currentId = $this->id;
        while ($currentId > 0) {
            if (isset($categories[$currentId])) {
                $orderedPath[] = $categories[$currentId];
            }
            $currentId = $categories[$currentId]->parent_id ?? 0;
        }

        return array_reverse($orderedPath);
    }

    /**
     * Get parent ID from cache or database
     */
    private function getParentIdFromCache($categoryId){
        static $parentCache = [];

        if (!isset($parentCache[$categoryId])) {
            $category = static::select('parent_id')->find($categoryId);
            $parentCache[$categoryId] = $category ? $category->parent_id : 0;
        }

        return $parentCache[$categoryId];
    }

    /**
     * Get optimized name using preloaded localizations
     */
    public function getOptimizedName($category){
        // If localization is already loaded, use it
        if($category->relationLoaded('localization') && $category->localization->isNotEmpty()){
            $localization = $category->localization->where('field', 'name')->first();
            if($localization){
                return $localization->value;
            }
        }

        // Fallback to original name property
        return $category->name;
    }

	/**
	 * Получение корневой категории от текущей
	 * @return $this|mixed
	 */
    public function get_root_category(){
        $categories = $this->get_parent_categories($this->id);

        if(count($categories) > 1)
            return $categories[count($categories) - 1];
        else
            return $this;
    }

	/**
	 * @return mixed
	 */
    public function all_categories_with_parent_name(){
        return $this->select('categories.*', 'p.name AS parent_name')
            ->leftJoin('categories AS p', 'categories.parent_id', '=', 'p.id')
            ->get();
    }

    static function getSelect($exclude = null){
	    $categories = [
		    (object)[
			    'name' => trans('locale.Not selected'),
			    'id' => null
		    ]
	    ];

	    // Get all categories
	    $query = Category::when($exclude, function($query) use ($exclude){
	        $query->where('id', '!=', $exclude);
	    });
	    $allCategories = $query->get();

	    if ($allCategories->isEmpty()) {
	        return $categories;
	    }

	    // Get all category IDs
	    $categoryIds = $allCategories->pluck('id')->toArray();

	    // Get all localizations in one query
	    $locale = app()->getLocale();
	    $localizations = Localization::whereIn('localizable_id', $categoryIds)
	        ->where('localizable_type', 'Categories')
	        ->where('language', $locale)
	        ->where('field', 'name')
	        ->get()
	        ->keyBy('localizable_id');

	    // Build the result with optimized localization access
	    foreach($allCategories as $c){
		    $categories[] = (object)[
			    'name' => $localizations[$c->id]->value ?? $c->getAttribute('name'),
			    'id' => $c->id
		    ];
	    }

	    return $categories;
    }

    public function getChildrenCategories($cat_id){
        if(isset($this->{'children_categories_'.$cat_id})){
            $categories = $this->{'children_categories_'.$cat_id};
        }else{
            $categories = Cache::remember('children_categories_'.$cat_id, 131040, function () use (&$cat_id) {
                $children_categories = $this->select('id')->where('parent_id', $cat_id)->with(['children' => function($query){
                    $query->select(['id', 'parent_id'])->with(['children' => function($query){
                        $query->select(['id', 'parent_id']);
                    }]);
                }])->get()->toArray();
                $categories = $this->flattenCategories($children_categories);
                return $categories;
            });

            $this->{'children_categories_'.$cat_id} = $categories;
        }

        return $categories;
    }

    private function flattenCategories($arr){
        $categories = [];
        foreach($arr as $cat){
            $categories[] = $cat['id'];
            if(!empty($cat['children'])){
                $categories = array_merge($categories, $this->flattenCategories($cat['children']));
            }
        }
        return $categories;
    }

	public function getParentCategories($cat_id){
		$category = $this->select('id', 'parent_id')->where('id', $cat_id)->with(['parent' => function($query){
			$query->select(['id', 'parent_id'])->with(['parent' => function($query){
				$query->select(['id', 'parent_id']);
			}]);
		}])->first();
        if(!empty($category)){
            $category = $category->toArray();
            $categories = $this->flattenParentCategories($category);
        }else{
            $categories = [];
        }
		return $categories;
	}

	private function flattenParentCategories($category){
		$categories = [$category['id']];
		if(!empty($category['parent'])){
			$categories = array_merge($categories, $this->flattenParentCategories($category['parent']));
		}
		return $categories;
	}

    /**
     * Список категорий со вложенностью
     *
     * @param null $exclude
     * @param bool $with_empty
     * @return array
     */
	public function getTreeList($exclude = null, $with_empty = true){
        $tree = $this->get_root_categories(null);
        $list = $this->getChildrenTreeList($tree, $exclude);

        if($with_empty){
            $list = array_merge([(object)['id' => '', 'name' => '-']], $list);
        }

        return $list;
    }

    private function getChildrenTreeList($tree, $exclude = null, $parent_name = ''){
        $list = [];

        if($tree->count()){
            foreach($tree as $category){
                if($category->id != $exclude){
                    $name = $parent_name.$category->name;
                    $list[] = (object)[
                        'id' => $category->id,
                        'name' => $name
                    ];
                    if($category->children->count()){
                        $list = array_merge($list, $this->getChildrenTreeList($category->children, $exclude, $name.' > '));
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Генерация уникального url
     *
     * @param $name
     * @param int $parent_id
     * @return string
     */
    public function generateUrlAlias($name, $parent_id = 0){
        $slug = Str::slug(Helper::translit($name));

        while(!empty($this->where('slug', $slug)->count())){
            if(!empty($parent_id)){
                $parent = $this->find($parent_id);
                if(!empty($parent)){
                    $slug = $parent->url_alias.'-'.$slug;
                    $parent_id = $parent->parent_id;
                }else{
                    $parent_id = 0;
                }
            }else{
                $slug .= '-'.rand(1, 100);
            }
        }

        return $slug;
    }

    public function getActionData(){
        return [
            'attributes' => [
                'id' => $this->id,
                'status' => $this->status
            ]
        ];
    }

    protected function dataMap(){
        return [
            'attributes' => [],
            'relations' => [
                'parent' => [
                    'attributes' => [
                        'id' => ''
                    ]
                ],
                'seo' => [
                    'attributes' => [
                        'id' => '',
                        'canonical' => '',
                        'robots' => '',
                        'url' => ''
                    ]
                ],
                'image' => [
                    'attributes' => [
                        'id' => '',
                        'path' => ''
                    ]
                ],
                'attributes' => [
                    'attributes' => [
                        'id' => ''
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
            'relations.parent' => [
                'name' => '',
                'multiple' => true,
                'fields' => [
                    'relations.localization' => [
                        'localization' => true,
                        'fields' => [
                            [
                                'name' => 'Родительская категория',
                                'field' => 'name'
                            ]
                        ]
                    ]
                ]
            ],
            'relations.image[].attributes.path' => [
                'name' => 'Изображение',
                'type' => 'file'
            ],
            'relations.attributes' => [
                'name' => 'Связанные атрибуты',
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
