<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Gallery;
use App\Models\ProductAttributes;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ProductsExport;
use App\Models\ProductsImport;
use App\Models\AttributeValue;
use App\Models\Localization;
use App\Models\Attribute;
use App\Models\Variation;
use App\Models\Redirect;
use App\Models\Category;
use App\Models\Product;
use App\Models\Action;
use App\Models\Setting;
use App\Models\Filter;
use App\Models\Sale;
use App\Models\File;
use App\Models\User;
use App;

class ProductsController extends Controller
{
    public $stocks = [];

    public function __construct()
    {
        $this->stocks = [
            '1' => trans('locale.in_stock'),
            '-2' => trans('locale.out_of_stock'),
            '0' => trans('locale.expected'),
            '-1' => trans('locale.on_order')
        ];
    }

    /**
     * Карточка товара
     *
     * @param $data
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
	public function showAction($data){
        $redirect = Redirect::where('old_url', '/'.str_replace(' ', '+', urldecode($data->request->path())))->where('old_url', '!=', 'new_url')->first();
        if(!empty($redirect) && $redirect->old_url != $redirect->new_url){
            return redirect($redirect->new_url, 301);
        }

		$product = $data->seo->seotable;

		if(empty($product)){
			abort(404);
		}

        $product->load(['localization', 'gallery.image.images', 'attributes' => function($query){
                $query->with(['info.localization', 'value.localization']);
            },
            'variations' => function($query){
                $query->with(['attribute_values.localization']);
            }
        ]);

		$viewed = json_decode($data->request->cookie('viewed'), true);

		if (!is_array($viewed)) {
			$viewed = [];
		}

		if (!in_array($product->id, $viewed)) {
			if (count($viewed) > 7) {
				array_splice($viewed, -7);
			}
			$viewed[] = $product->id;
		}

        $locale = App::getLocale();
        $settings = new Setting;

        $reviews = $product->reviews()->where('published', 1)->get();

        $seo = $product->seo;
        if(empty($seo->meta_title) || $seo->meta_title == $product->name){
            $category = $product->categories()->first();
            $seo->meta_title = $product->name . ' buy in UK. ' . (!empty($category) ? ' '.$category->name : '') . ' Online';
        }
        if(empty($seo->meta_description) || $seo->meta_description == $product->name){
            $category = $product->categories()->first();
            $seo->meta_description = 'Shop ' . $product->name . '. Premium quality ' . (!empty($category) ? ' '.$category->name : '') . ' and products with UK delivery. Order now.';
        }

        $attributes = [];
        foreach($product->attributes()->with('info.localization', 'value.localization')->get() as $attribute){
            if($attribute->info->visible){
                if(!isset($attributes[$attribute->info->name]))
                    $attributes[$attribute->info->name] = [];

                $attributes[$attribute->info->name][] = $attribute->value->name.$attribute->info->unit;
            }
        }

        $variations = $product->variations_attributes();

        // Если для товара вручную не подобраны similar_products — подставляем похожие по эмбеддингу
        $similar = $product->similar;
        if ($similar->isEmpty()) {
            $similar = $product->getSimilarByEmbedding(8);
        }

		return response(view('public.product')
			->with('product', $product)
			->with('seo', $seo)
			->with('similar', $similar)
			->with('bought_together', $product->getBoughtTogether(8))
			->with('attributes', $attributes)
            ->with('variations_prices', $variations['variations_prices'])
            ->with('variations', $variations['variations_attrs'])
            ->with('selected_variation_attributes', $variations['selected_variation_attributes'])
            ->with('reviews', $reviews)
            ->with('grade', $product->grade)
            ->with('delivery_information', $settings->get_setting('delivery_information_'.$locale))
			->with('viewed', !is_null($viewed) ? $product->getProducts($viewed) : null)
            ->withShortcodes())
			->withCookie(cookie()->forever('viewed', json_encode($viewed)));
	}

    /**
     * Список товаров в админ панели
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function adminIndexAction(){
        $categories = new Category();

        return view('admin.products.index', [
            'categories' => $categories->getTreeList(),
            'actions' => Sale::select('sales.id', 'localization.value as name')->leftJoin('localization', function($join) {
                $join->on('sales.id', '=', 'localization.localizable_id')
                    ->where('localization.localizable_type', '=', 'Sales')
                    ->where('localization.language', '=', env('APP_LOCALE'))
                    ->where('field', 'name');
            })->where('status', 1)->get(),
            'all_attributes' => $this->getOptimizedAttributes(),
            'localization' => json_encode(['datatable' => trans('datatable')])
        ]);
    }

    private function getOptimizedAttributes()
    {
        $locale = app()->getLocale();

        // Get all attributes first
        $attributes = Attribute::all();

        if ($attributes->isEmpty()) {
            return [];
        }

        // Get all attribute IDs
        $attributeIds = $attributes->pluck('id')->toArray();

        // Get all attribute localizations in one query
        $attributeLocalizations = Localization::whereIn('localizable_id', $attributeIds)
            ->where('localizable_type', 'Attributes')
            ->where('language', $locale)
            ->where('field', 'name')
            ->get()
            ->keyBy('localizable_id');

        // Get all attribute values for these attributes
        $attributeValues = AttributeValue::whereIn('attribute_id', $attributeIds)->get();

        if ($attributeValues->isEmpty()) {
            return $attributes->map(function($attribute) use ($attributeLocalizations) {
                return [
                    'id' => $attribute->id,
                    'name' => $attributeLocalizations[$attribute->id]->value ?? $attribute->getAttribute('name'),
                    'unit' => $attribute->unit,
                    'is_variation_attribute' => $attribute->is_variation_attribute,
                    'values' => []
                ];
            })->toArray();
        }

        // Get all value IDs
        $valueIds = $attributeValues->pluck('id')->toArray();

        // Get all value localizations in one query
        $valueLocalizations = Localization::whereIn('localizable_id', $valueIds)
            ->where('localizable_type', 'AttributeValues')
            ->where('language', $locale)
            ->where('field', 'name')
            ->get()
            ->keyBy('localizable_id');

        // Group values by attribute_id
        $valuesByAttribute = $attributeValues->groupBy('attribute_id');

        // Build the result
        return $attributes->map(function($attribute) use ($attributeLocalizations, $valuesByAttribute, $valueLocalizations) {
            $values = $valuesByAttribute->get($attribute->id, collect());

            return [
                'id' => $attribute->id,
                'name' => $attributeLocalizations[$attribute->id]->value ?? $attribute->getAttribute('name'),
                'unit' => $attribute->unit,
                'is_variation_attribute' => $attribute->is_variation_attribute,
                'values' => $values->map(function($value) use ($valueLocalizations) {
                    return [
                        'id' => $value->id,
                        'attribute_id' => $value->attribute_id,
                        'name' => $valueLocalizations[$value->id]->value ?? $value->value,
                        'value' => $value->value,
                        'sort_order' => $value->sort_order ?? 0
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    /**
     * Подгрузка товаров в список
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }
        $locale = app()->getLocale();
        $select = ['products.id', 'products.sku', 'products.stock', 'products.visible', 'products.original_price', 'products.file_id', 'products.sort_priority', 'localization.value as name'];

        $query = $this->adminGetFilteredQuery($request)->select($select)->leftJoin('localization', function($leftJoin) use($locale){
            $leftJoin->on('products.id', '=', 'localization.localizable_id')
                ->where('localization.localizable_type', '=', 'Products')
                ->where('field', 'name')
                ->where('language', $locale);
        });

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'], $order['dir']);
            }
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $products = $query->get();

        $data = [];
        foreach($products as $product){
            $actions = [];
            if($user->hasAccess(['products.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/products/edit/'.$product->id)
                ];
            }
            if($user->hasAccess(['attributes.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $product->id,
                    'name' => $product->name
                ];
            }

            $categories = [];
            foreach($product->categories as $category){
                $categories[$category->id] = $category->name;
            }

            $data[] = [
                'id' => ['id' => $product->id],
                'image' => !empty($product->image) ? $product->image->url([64, 64]) : null,
                'name' => ['name' => $product->name],
                'sku' => $product->sku,
                'price' => $product->original_price,
                'categories' => $categories,
                'stock' => isset($this->stocks[$product->stock]) ? __($this->stocks[$product->stock]) : $product->stock,
                'visible' => ['id' => $product->id, 'visible' => $product->visible],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Product::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Получение id отфильтрованных товаров
     *
     * @param Request $request
     */
    public function adminGetFilteredIdsAction(Request $request){
        $query = $this->adminGetFilteredQuery($request);
        $products = $query->get()->pluck('id');

        echo implode(',', $products->toArray());
    }

    /**
     * Подготовка запроса фильтра товаров
     *
     * @param Request $request
     * @return mixed
     */
    private function adminGetFilteredQuery(Request $request){
        $query = Product::select('products.id');

        if($request->has('search.value')){
            $text = $request->search['value'];

            $ids_loc = Localization::select('localizable_id')
                ->where('localizable_type', '=', 'Products')
                ->where('field', 'name')
                ->where('value', 'like', '%' . $text . '%')
                ->get()
                ->pluck('localizable_id')
                ->toArray();

            $ids_prod = Product::select('id')
                ->orWhere('products.sku', 'like', '%' . $text . '%')
                ->orWhere('products.external_id', 'like', '%' . $text . '%')
                ->get()
                ->pluck('id')
                ->toArray();

            $query->whereIn('products.id', array_unique(array_merge($ids_loc, $ids_prod)));
        }

        if(!empty($request->filter)){
            if(env('REDIS_CACHE')){
                $ids = [];
                $rules = [];

                // Входные данные
                foreach($request->filter as $group){
                    $group_rules = [
                        'and' => [],
                        'or' => []
                    ];

                    if(isset($group[0]) && isset($group[0]['relations'])){
                        $group_rules['relations'] = $group[0]['relations'];
                        unset($group[0]['relations']);
                    }else{
                        $group_rules['relations'] = 'AND';
                    }

                    foreach($group as $rule){
                        if(!empty($rule['criterion'])){
                            if($rule['criterion'] == 'category' && !empty($rule['value'])){
                                $key = "category_".$rule['value'];
                            }elseif($rule['criterion'] == 'attribute' && !empty($rule['value'])){
                                $key = "attribute_".$rule['value'];
                            }elseif($rule['criterion'] == 'stock'){
                                if($rule['value'] > 0){
                                    $key = "product_stock";
                                }elseif($rule['value'] == -2){
                                    $key = "product_not_stock";
                                }elseif($rule['value'] == -1){
                                    $key = "product_under_the_order";
                                }elseif($rule['value'] == 0){
                                    $key = "product_expected";
                                }
                            }elseif($rule['criterion'] == 'price'){
                                $key = null;
                                if($rule['condition'] == '='){
                                    $key = "prices_".($rule['value']*100);
                                    $ids = Redis::command("zrangebyscore", ["prices", $rule['value']*100, $rule['value']*100]);
                                }elseif($rule['condition'] == '>'){
                                    $key = "prices_more_".(($rule['value']*100));
                                    $ids = Redis::command("zrangebyscore", ["prices", '('.($rule['value']*100), "+inf"]);
                                }elseif($rule['condition'] == '<'){
                                    $key = "prices_less_".(($rule['value']*100));
                                    $ids = Redis::command("zrangebyscore", ["prices", "-inf", '('.($rule['value']*100)]);
                                }

                                if(!empty($key)){
                                    foreach(array_chunk($ids, 1000) as $ids_chunk) {
                                        $command = [$key];

                                        foreach($ids_chunk as $id) {
                                            $command[] = 'SET';
                                            $command[] = 'u1';
                                            $command[] = $id;
                                            $command[] = 1;
                                        }

                                        Redis::command('bitfield', $command);
                                    }
                                }
                            }elseif($rule['criterion'] == 'description'){
                                if($rule['condition'] == '='){
                                    $ids_desc = Localization::select('localizable_id')
                                        ->where('localizable_type', '=', 'Products')
                                        ->where('field', 'description')
                                        ->where('value', $rule['value'])
                                        ->get()
                                        ->pluck('localizable_id')
                                        ->toArray();

                                    $query->whereIn('products.id', $ids_desc);
                                }elseif($rule['condition'] == '%'){
                                    $ids_desc = Localization::select('localizable_id')
                                        ->where('localizable_type', '=', 'Products')
                                        ->where('field', 'description')
                                        ->where('value', 'like', '%'.$rule['value'].'%')
                                        ->get()
                                        ->pluck('localizable_id')
                                        ->toArray();

                                    $query->whereIn('products.id', $ids_desc);
                                }
                            }
                        }

                        if(!empty($key))
                            $group_rules[isset($rule['relations']) && $rule['relations'] == 'OR' ? 'or' : 'and'][] = $key;
                    }

                    $rules[] = $group_rules;
                }

                // Вычисление групп
                $groups_keys = [];
                foreach($rules as $group_id => $group_rules){
                    $and_key = null;
                    if(!empty($group_rules['and'])){
                        if(count($group_rules['and']) > 1){
                            $and_key = "group_and_$group_id";
                            $params = ["and", $and_key];
                            foreach($group_rules['and'] as $result){
                                $params[] = $result;
                            }
                            Redis::command('bitop', $params);
                        }else{
                            $and_key = $group_rules['and'][0];
                        }
                    }

                    if(!empty($group_rules['or'])){
                        $groups_keys[] =
                            [
                                'key' => "group_$group_id",
                                'relations' => $group_rules['relations']
                            ];
                        $params = ["or", "group_$group_id", $and_key];
                        foreach($group_rules['or'] as $result){
                            $params[] = $result;
                        }
                        Redis::command('bitop', $params);
                    }elseif(!empty($and_key)){
                        $groups_keys[] =
                            [
                                'key' => $and_key,
                                'relations' => $group_rules['relations']
                            ];
                    }
                }

                // Вычисление результата
                $and_keys = [];
                foreach($groups_keys as $group_id => $key){
                    if($key['relations'] == 'AND'){
                        $and_keys[] = $key['key'];
                        unset($groups_keys[$group_id]);
                    }
                }

                $and_key = null;
                if(count($and_keys) > 1){
                    $and_key = "result_and";
                    $params = ["and", $and_key];
                    foreach($and_keys as $key){
                        $params[] = $key;
                    }
                    Redis::command('bitop', $params);
                }elseif(isset($and_keys[0])){
                    $and_key = $and_keys[0];
                }

                if(!empty($and_key)){
                    $groups_keys[] = [
                        'key' => $and_key,
                        'relations' => 'OR'
                    ];
                }

                $result_key = null;
                if(count($groups_keys) > 1){
                    $result_key = 'result';
                    $params = ["or", $result_key];
                    foreach($groups_keys as $key){
                        $params[] = $key['key'];
                    }
                    Redis::command('bitop', $params);
                }elseif(!empty($groups_keys)){
                    $key = array_shift($groups_keys);
                    $result_key = $key['key'];
                }

                if(isset($result_key)){
                    $bitmap = Redis::get($result_key);
                    $ids = $this->bitmap_ids($bitmap);

                    $query->whereIn('products.id', $ids);
                }
            }else{
                $relations = [];
                foreach($request->filter as $group){
                    foreach ($group as $condition){
                        if($condition['criterion'] == 'category'){
                            $relations[] = ['product_categories', 'product_categories.product_id', '=', 'products.id'];
                        }elseif($condition['criterion'] == 'attribute'){
                            $relations[] = ['product_attributes', 'product_attributes.product_id', '=', 'products.id'];
                        }
                    }

                    $relation = isset($group[0]['relations']) && $group[0]['relations'] == 'OR' ? 'orWhere' : 'where';

                    $query->{$relation}(function ($query) use($group){
                        foreach ($group as $condition){
                            $relation = isset($condition['relations']) && $condition['relations']=='OR' ? 'orWhere' : 'where';
                            if($condition['criterion'] == 'category') {
                                if(empty($condition['value'])){
                                    $query->doesntHave('categories');
                                }else{
                                    if ($condition['condition'] == 'with_child') {
                                        $category = Category::find($condition['value']);
                                        $query->{$relation . 'In'}('product_categories.category_id', array_merge([$category->id], $category->getChildrenCategories($category->id)));
                                    } else {
                                        $query->{$relation}('product_categories.category_id', $condition['value']);
                                    }
                                }
                            }elseif($condition['criterion'] == 'attribute'){
                                if(!empty($condition['value'])){
                                    $query->{$relation}('product_attributes.attribute_value_id', $condition['value']);
                                }elseif(!empty($condition['attribute'])){
                                    $query->{$relation}('product_attributes.attribute_id', $condition['attribute']);
                                }
                            }elseif($condition['criterion'] == 'stock'){
                                $query->{$relation}('products.stock', $condition['value']);
                            }elseif($condition['criterion'] == 'price'){
                                $query->{$relation}('products.price', $condition['condition'], $condition['value']);
                            }elseif($condition['criterion'] == 'description'){
                                if($condition['condition'] == '='){
                                    $query->{$relation}('products.description', $condition['value']);
                                }elseif($condition['condition'] == '%'){
                                    $query->{$relation}('products.description', 'like', '%'.$condition['value'].'%');
                                }
                            }
                        }
                    });
                }
                foreach ($relations as $relation){
                    $query->leftJoin($relation[0], $relation[1], $relation[2], $relation[3]);
                }
            }
        }

        return $query;
    }

    /**
     * Массовое обновление
     *
     * @param $action
     * @param Request $request
     * @return array
     */
    public function adminMassAction($action, Request $request){
        if($action == 'add_category'){
            $category = Category::find($request->category);
            if(!empty($category)){
                $products = Product::select(['products.id'])
                    ->whereIn('products.id', explode(',', $request->products))
                    ->whereDoesntHave('categories', function ($query) use($category) {
                        $query->where(['categories.id' => $category->id]);
                    })
                    ->get();
                $product_categories = [];
                if(!empty($products)){
                    foreach ($products as $product){
                        $product_categories[] = [
                            'product_id' => $product->id,
                            'category_id' => $category->id
                        ];
                    }

                    DB::table('product_categories')->insert($product_categories);
                    return ['result' => 'success', 'message' => trans('locale.products.added_to_category', ['count' => count($product_categories), 'category' => $category->name])];
                }else{
                    return ['result' => 'error', 'message' => trans('locale.products.nothing_to_update')];
                }
            }else{
                return ['result' => 'error', 'message' => trans('locale.products.category_not_found')];
            }
        }elseif($action == 'remove_category'){
            $category = Category::find($request->category);
            if(!empty($category)) {
                $count = DB::table('product_categories')
                    ->whereIn('product_id', explode(',', $request->products))
                    ->where('category_id', $category->id)
                    ->count();
                if(!empty($count)){
                    DB::table('product_categories')
                        ->whereIn('product_id', explode(',', $request->products))
                        ->where('category_id', $category->id)
                        ->delete();
                    return ['result' => 'success', 'message' => trans('locale.products.removed_from_category', ['count' => $count, 'category' => $category->name])];
                }else{
                    return ['result' => 'error', 'message' => trans('locale.products.nothing_to_update')];
                }
            }else{
                return ['result' => 'error', 'message' => trans('locale.products.category_not_found')];
            }
        }elseif($action == 'change_categories'){
            $category = Category::find($request->category);
            if(!empty($category)) {
                $count = DB::table('product_categories')
                    ->whereIn('product_id', explode(',', $request->products))
                    ->count();
                if(!empty($count)){
                    DB::table('product_categories')
                        ->whereIn('product_id', explode(',', $request->products))
                        ->where('category_id', '!=', $category->id)
                        ->delete();

                    $products = Product::select(['products.id'])
                        ->whereIn('products.id', explode(',', $request->products))
                        ->whereDoesntHave('categories', function ($query) use($category) {
                            $query->where(['categories.id' => $category->id]);
                        })
                        ->get();
                    $product_categories = [];
                    if(!empty($products)){
                        foreach ($products as $product){
                            $product_categories[] = [
                                'product_id' => $product->id,
                                'category_id' => $category->id
                            ];
                        }

                        DB::table('product_categories')->insert($product_categories);
                    }

                    return ['result' => 'success', 'message' => trans('locale.products.moved_to_category', ['count' => $count, 'category' => $category->name])];
                }else{
                    return ['result' => 'error', 'message' => trans('locale.products.nothing_to_update')];
                }
            }else{
                return ['result' => 'error', 'message' => trans('locale.products.category_not_found')];
            }
        }elseif($action == 'remove_products'){
            $count = Product::whereIn('products.id', explode(',', $request->products))
                ->count();
            Product::whereIn('products.id', explode(',', $request->products))
                ->delete();
            return ['result' => 'success', 'message' => trans_choice('locale.products.deleted_count', $count, ['count' => $count])];
        }elseif($action == 'change_status'){
	        $count = Product::whereIn('products.id', explode(',', $request->products))->where('stock', '!=', $request->status)
	                        ->count();
	        Product::whereIn('products.id', explode(',', $request->products))->where('stock', '!=', $request->status)
	               ->update(['stock' => $request->status]);
	        return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }elseif($action == 'change_sort_priority'){
            $count = Product::whereIn('products.id', explode(',', $request->products))->count();
            Product::whereIn('products.id', explode(',', $request->products))
                ->update(['sort_priority' => $request->sort_priority]);
            return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }elseif($action == 'add_price'){
	        $count = Product::whereIn('products.id', explode(',', $request->products))
	                        ->count();
	        Product::whereIn('products.id', explode(',', $request->products))
		            ->increment('original_price', $request->num);
	        return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }elseif($action == 'add_sale_price'){
	        $count = Product::whereIn('products.id', explode(',', $request->products))
	                        ->count();
	        Product::whereIn('products.id', explode(',', $request->products))
	               ->increment('sale_price', $request->num);
	        return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }elseif($action == 'multiply_price'){
	        $count = Product::whereIn('products.id', explode(',', $request->products))
	                        ->count();
	        Product::whereIn('products.id', explode(',', $request->products))
	               ->update(['original_price' => DB::raw("original_price * $request->num")]);
	        return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }elseif($action == 'multiply_sale_price'){
	        $count = Product::whereIn('products.id', explode(',', $request->products))
	                        ->count();
	        Product::whereIn('products.id', explode(',', $request->products))
	            ->update(['sale_price' => DB::raw("sale_price * $request->num")]);
	        return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }elseif($action == 'multiply_sale'){
            $count = Product::whereIn('products.id', explode(',', $request->products))
                ->count();

            $k = 1 - $request->sale_percent / 100;
            $sale_from = date('Y-m-d', strtotime($request->sale_from));
            $sale_to = date('Y-m-d', strtotime($request->sale_to));

            Product::whereIn('products.id', explode(',', $request->products))->update([
                'sale_price' => DB::raw("original_price * $k"),
                'sale' => 1,
                'sale_from' => $sale_from,
                'sale_to' => $sale_to
            ]);

            $date = date('Y-m-d H:i:s');

            Product::where('sale', 1)
                ->where('sale_from', '<=', $date)
                ->where('sale_to', '>=', $date)
                ->where('price', '!=', DB::raw('sale_price'))
                ->update(['price' => DB::raw('sale_price')]);

            $sale = Sale::find(1);
            $products = Product::select(['id', 'price'])->whereIn('products.id', explode(',', $request->products))->get();
            $data = [];
            foreach($products as $product){
                $data[$product->id] = [
                    'sale_id' => 1,
                    'sale_price' => $product->price
                ];
            }
            $sale->products()->syncWithoutDetaching($data);

            foreach(Product::whereIn('products.id', explode(',', $request->products))->get() as $product){
                $sale = $product->original_price('sale');
                $product->savePrices(['sale_price' => $sale->price * $request->num]);
            }

            if(env('REDIS_CACHE')) {
                foreach($products as $product){
                    Redis::command('zadd', ['prices', $product->price * 100, $product->id]);
                }
                $products = $sale->products()->select('product_id')->get()->pluck('product_id')->unique()->sort()->values()->all();

                Redis::command('del', ['sale_1']);
                foreach(array_chunk($products, 100) as $data){
                    $command = ['sale_1'];

                    foreach($data as $product_id){
                        $command[] = 'SET';
                        $command[] = 'u1';
                        $command[] = $product_id;
                        $command[] = 1;
                    }

                    Redis::command('bitfield', $command);
                }
            }

            return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }elseif($action == 'change_attributes'){
            $products = ProductAttributes::whereIn('product_id',  explode(',', $request->products))
                ->where('attribute_value_id', $request->find_attr_val)
                ->get();

            $count = $products->count();

            if($count){
                ProductAttributes::whereIn('product_id',  explode(',', $request->products))
                    ->where('attribute_value_id', $request->find_attr_val)
                    ->update(['attribute_value_id' => $request->new_attr_val]);

                $values = AttributeValue::whereIn('id', [$request->find_attr_val, $request->new_attr_val])->get();
                foreach($values as $value){
                    $products = $value->products()->select('product_id')->get()->pluck('product_id')->unique()->sort()->values()->all();

                    Redis::command('del', ['attribute_'.$value->id]);
                    foreach(array_chunk($products, 100) as $data){
                        $command = ['attribute_'.$value->id];

                        foreach($data as $product_id){
                            $command[] = 'SET';
                            $command[] = 'u1';
                            $command[] = $product_id;
                            $command[] = 1;
                        }

                        Redis::command('bitfield', $command);
                    }
                }
            }

            return ['result' => 'success', 'message' => trans_choice('locale.products.updated_count', $count, ['count' => $count])];
        }

        return ['result' => 'error', 'message' => trans('locale.errors.invalid_method')];
    }

    /**
     * Страница создания товара
     *
     * @return \Illuminate\Http\Response
     */
    public function adminCreateAction(){
        return view('admin.products.create')
            ->with('categories', Category::getSelect())
            ->with('attributes', $this->getOptimizedAttributes())
            ->with('languages', Config::get('app.locales_names'))
            ->with('editors', Helper::localizationFields(['description']));
    }

	/**
	 * Создание товара
	 *
	 * @param Request $request
	 * @param Product $products
	 *
	 * @return $this
	 */
    public function adminStoreAction(Request $request, Product $products){
        $rules = [];
        $messages = [];
        foreach(Helper::localizationFields(['name']) as $key){
            $rules[$key] = 'required';
            $messages[$key.'.required'] = trans('locale.products.validation.field_required');
        }
        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $data = $request->only($products->getFillable());

        foreach(['stock', 'sale', 'sort_priority'] as $key){
            if(empty($data[$key]))
                $data[$key] = 0;
        }

	    $products->fill($data);

        $current_time = time();
        $sale_from = empty($data['sale_from']) ? null : strtotime($data['sale_from']);
        $sale_to = empty($data['sale_to']) ? null : strtotime($data['sale_to']);
        if(!empty($data['sale']) && !empty($data['sale_price']) && (empty($sale_from) || $sale_from <= $current_time) && (empty($sale_to) || $sale_to >= $current_time)){
            $products->price = $data['sale_price'];
        }else{
            $products->price = $data['original_price'];
        }

        $products->sale_from = empty($sale_from) ? null : date('Y-m-d', $sale_from);
        $products->sale_to = empty($sale_to) ? null : date('Y-m-d', $sale_to);

	    $products->save();
	    $products->saveSeo($request);
	    $products->saveLocalization($request);
        $products->saveGalleries($request);

	    $products->categories()->sync($request->product_category_id);

        $products->load('seo');

        if(env('REDIS_CACHE')) {
            Redis::command('setbit', ["product_visible", $products->id, empty($products->visible) ? 0 : 1]);
            Redis::command('setbit', ["product_stock", $products->id, $products->stock > 0 ? 1 : 0]);
            Redis::command('setbit', ["product_not_stock", $products->id, $products->stock === -2 ? 1 : 0]);
            Redis::command('setbit', ["product_under_the_order", $products->id, $products->stock === -1 ? 1 : 0]);
            Redis::command('setbit', ["product_expected", $products->id, $products->stock === 0 ? 1 : 0]);
            Redis::command('setbit', ["product_sale", $products->id, (int)$products->sale === 1 && strtotime($products->sale_from) <= time() && strtotime($products->sale_to) >= time() ? 1 : 0]);
            Redis::command('zadd', ['prices', $products->price * 100, $products->id]);
            Redis::command('zadd', ['sort_priority', $products->sort_priority * 100, $products->id]);
            Redis::command('zadd', ['popularity', $products->popularity * 100, $products->id]);
            Redis::command('zadd', ['ratings', $products->ratings * 100, $products->id]);

            if(!empty($request->product_category_id)){
                foreach($request->product_category_id as $category_id){
                    Redis::command('setbit', ["category_$category_id", $products->id, 1]);
                }
            }
        }

	    Action::createEntity($products);

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.products.created_success'),
            'redirect' => '/admin/products/edit/'.$products->id
        ]);
    }

    /**
     * Изменение товара
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\View\View|object
     */
    public function adminEditAction($id) {
        $product = Product::find($id);

        if(empty($product)) {
            abort(404, trans('locale.products.not_found'));
        }

        $categories = [];
        if(!empty($product->categories)){
            foreach ($product->categories as $category){
                $categories[] = $category->id;
            }
        }

        $attributes = [];
        foreach(collect($product->attributes()->select(['attribute_id', 'attribute_value_id'])->get()->toArray())->unique() as $attr){
            $attributes[$attr['attribute_id']][] = $attr['attribute_value_id'];
        }
        $product_attributes = [];
        foreach($attributes as $attribute => $values){
            $product_attributes[] = [
                'attribute_id' => $attribute,
                'values' => $values
            ];
        }

        $sets = Product::where('id', '<>', $id)->get();

        // Get all product IDs for localization loading
        $productIds = $sets->pluck('id')->toArray();

        // Get all product localizations in one query
        $locale = app()->getLocale();
        $productLocalizations = Localization::whereIn('localizable_id', $productIds)
            ->where('localizable_type', 'Products')
            ->where('language', $locale)
            ->where('field', 'name')
            ->get()
            ->keyBy('localizable_id');

        // Attach localizations to products to avoid N+1 queries
        foreach ($sets as $set_product) {
            if (isset($productLocalizations[$set_product->id])) {
                $set_product->setAttribute('localized_name', $productLocalizations[$set_product->id]->value);
            } else {
                $set_product->setAttribute('localized_name', $set_product->getAttribute('name'));
            }
        }

        // Convert collection to array with objects for template
        $setsArray = $sets->map(function($product) {
            return (object)[
                'id' => $product->id,
                'name' => $product->localized_name ?? $product->getAttribute('name'),
                // Add other product fields as needed
                'sku' => $product->sku,
                'price' => $product->price
            ];
        })->toArray();

        return view('admin.products.edit')
            ->with('product', $product)
            ->with('product_attributes', $product_attributes)
            ->with('categories', Category::getSelect())
            ->with('added_categories', $categories)
            ->with('attributes', $this->getOptimizedAttributes())
            ->with('seo', $product->seo)
	        ->with('languages', Config::get('app.locales_names'))
            ->with('sets', $setsArray)
            ->with('related', $product->related->pluck('id')->toArray())
            ->with('similar', $product->similar->pluck('id')->toArray())
	        ->with('editors', Helper::localizationFields(['description', 'characteristics', 'seo_description']));
    }

    /**
     * Обновление товара
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminUpdateAction(Request $request, $id){
        $rules = [];
        $messages = [];
        foreach(Helper::localizationFields(['name']) as $key){
            $rules[$key] = 'required';
            $messages[$key.'.required'] = trans('locale.products.validation.field_required');
        }
        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()){
            return response()->json([
                'result' => 'error',
                'errors' => $validator->messages(),
                'message' => trans('locale.Form validation error')
            ], 200);
        }

        $product = Product::find($id);

        if(empty($product)) {
            return response()->json([
                'result' => 'error',
                'message' => trans('locale.products.not_found')
            ], 404);
        }

        $product_data = $product->fullData();

        $data = $request->only($product->getFillable());

        foreach(['stock', 'sale', 'sort_priority'] as $key){
            if(empty($data[$key]))
                $data[$key] = 0;
        }

	    $product->fill($data);

	    $current_time = time();
        $sale_from = empty($data['sale_from']) ? null : strtotime($data['sale_from']);
        $sale_to = empty($data['sale_to']) ? null : strtotime($data['sale_to']);
        if(!empty($data['sale']) && !empty($data['sale_price']) && (empty($sale_from) || $sale_from <= $current_time) && (empty($sale_to) || $sale_to >= $current_time)){
            $product->price = $data['sale_price'];
        }else{
            $product->price = $data['original_price'];
        }

        $product->sale_from = empty($sale_from) ? null : date('Y-m-d', $sale_from);
        $product->sale_to = empty($sale_to) ? null : date('Y-m-d', $sale_to);

        if(env('REDIS_CACHE')) {
            Redis::command('setbit', ["product_visible", $product->id, empty($product->visible) ? 0 : 1]);
            Redis::command('setbit', ["product_stock", $product->id, (int)$product->stock > 0 ? 1 : 0]);
            Redis::command('setbit', ["product_not_stock", $product->id, (int)$product->stock === -2 ? 1 : 0]);
            Redis::command('setbit', ["product_under_the_order", $product->id, (int)$product->stock === -1 ? 1 : 0]);
            Redis::command('setbit', ["product_expected", $product->id, (int)$product->stock === 0 ? 1 : 0]);
            Redis::command('setbit', ["product_sale", $product->id, (int)$product->sale === 1 && strtotime($product->sale_from) <= time() && strtotime($product->sale_to) >= time() ? 1 : 0]);
            Redis::command('zadd', ['prices', $product->price * 100, $product->id]);
            Redis::command('zadd', ['sort_priority', $product->sort_priority * 100, $product->id]);
            Redis::command('zadd', ['popularity', $product->popularity * 100, $product->id]);
            Redis::command('zadd', ['ratings', $product->ratings * 100, $product->id]);
        }
	    $product->push();
	    $product->saveLocalization($request);
	    $product->saveGalleries($request);

        // Обновление коллекций категорий Redis
        if(env('REDIS_CACHE')) {
            $current_categories = $product->categories->pluck('id')->toArray();
            $new_categories = [];
            $category = new Category();
            if(!empty($request->product_category_id)){
                foreach ($request->product_category_id as $category_id) {
                    $ids = $category->getParentCategories($category_id);
                    foreach($ids as $id){
                        if(!empty($id))
                            $new_categories[] = $id;
                    }
                }
            }
            foreach($current_categories as $category_id){
                Redis::command('setbit', ["category_$category_id", $product->id, 0]);
            }
            foreach(array_unique($new_categories) as $category_id){
                Redis::command('setbit', ["category_$category_id", $product->id, 1]);
            }
        }
        $product->categories()->sync($request->product_category_id);

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.products.updated_success'),
            'redirect' => '/admin/products/edit/'.$product->id
        ]);
    }

    /**
     * Обновление атрибутов товара
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminSyncAttributesAction(Request $request, $id){
        $data = $request->only(['attributes']);
        $product = Product::find($id);

        if(empty($product)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Product not found')]);
        }

        $product->updateAttributes($data['attributes']);

        return response()->json(['result' => 'success', 'message' => trans('locale.The list of attributes has been updated')]);
    }


    /**
     * Обновление связей товара
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function adminUpdateRelatedAction(Request $request, $id)
    {
        $product = Product::find($id);

        if(empty($product)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Product not found')]);
        }

        if(!empty($request->related)){
            foreach(Product::whereIn('id', $request->related)->get() as $prod){
                $r = [$product->id];
                foreach($request->related as $rel_id){
                    if($rel_id != $prod->id)
                        $r[] = $rel_id;
                }
                $prod->related()->syncWithoutDetaching($r);
            }
        }
        $product->related()->sync($request->related);

        if(!empty($request->similar)){
            foreach(Product::whereIn('id', $request->similar)->get() as $prod){
                $r = [$product->id];
                foreach($request->similar as $rel_id){
                    if($rel_id != $prod->id)
                        $r[] = $rel_id;
                }
                $prod->similar()->syncWithoutDetaching($r);
            }
        }
        $product->similar()->sync($request->similar);

        return response()->json(['result' => 'success', 'message' => trans('locale.The list of connections has been updated')]);
    }

    /**
     * Обновление SEO данных товара
     *
     * @param Request $request
     * @param Product $products
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSeoAction(Request $request, Product $products, $id){
        $product = $products->find($id);

        if(empty($product)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Product not found')], 200);
        }

        $seo_data = $product->seo->fullData();
        $product->saveSeo($request);
        $product->load('seo');
        $product->slug = Str::slug(str_replace(['/', '_'], '', $product->seo->url));

        Action::updateEntity($product->seo, $seo_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
    }

    /**
     * Обновление статуса отображения товара
     *
     * @param Request $request
     * @param Product $products
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStatusAction(Request $request, Product $products, $id){
        $product = $products->find($id);

        if(empty($product)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Product not found')], 200);
        }
        $product_data = $product->getActionData();
        $product->visible = (int)$request->status;
        $product->save();

        Action::updateEntity($product, $product_data);

        if($product->visible)
            return response()->json(['result' => 'success', 'message' => trans('locale.Product enabled')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.Product disabled')], 200);
    }

    /**
     * Обновление цены товара
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdatePriceAction(Request $request, $id){
        $user = Sentinel::check();
        if(!$user->hasAccess(['products.view'])){
            return response()->json(['result' => 'error']);
        }

        $product = Product::find($id);
        $product->original_price = $request->price;

        $current_time = time();
        if(!empty($product->sale) && !empty($product->sale_price) && $product->sale_from <= $current_time && $product->sale_to >= $current_time){
            $product->price = $product->sale_price;
        }else{
            $product->price = $request->price;
        }

        $product->save();

        if(env('REDIS_CACHE')) {
            Redis::command('zadd', ['prices', $product->price * 100, $product->id]);
            Redis::command('zadd', ['sort_priority', $product->sort_priority * 100, $product->id]);
            Redis::command('zadd', ['popularity', $product->popularity * 100, $product->id]);
            Redis::command('zadd', ['ratings', $product->ratings * 100, $product->id]);
        }

        return response()->json(['result' => 'success']);
    }

    /**
     * Копирование товара
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function adminDuplicateAction($id){
        $product = new Product();
        $original_product = Product::with(['localization', 'categories', 'attributes', 'seo.localization', 'variations.attribute_values', 'galleries'])->find($id)->toArray();

        $product_data = [];
        foreach($product->getFillable() as $key){
            $product_data[$key] = $original_product[$key];
        }

        $product_categories = [];
        foreach($original_product['categories'] as $category){
            $product_categories[] = $category['id'];
        }

        $gallery = [];
        foreach($original_product['galleries'] as $image){
            $gallery[] = $image['file_id'];
        }

        $request = new Request();
        $request_data = [
            'canonical' => '',
            'robots' => '',
            'url' => null,
            'gallery' => $gallery
        ];
        foreach($original_product['localization'] as $localization){
            $request_data[$localization['field'].'_'.$localization['language']] = $localization['value'];
        }
        foreach($original_product['seo']['localization'] as $localization){
            $request_data[$localization['field'].'_'.$localization['language']] = $localization['value'];
        }
        $request_data['variations'] = [];
        foreach($original_product['variations'] as $variation){
            $variation_attributes = [];
            foreach($variation['attribute_values'] as $attr){
                $variation_attributes[] = $attr['id'];
            }
            $request_data['variations'][] = [
                'price' => $variation['price'],
                'stock' => $variation['stock'],
                'id' => $variation_attributes
            ];
        }

        $request->merge($request_data);
        $product->fill($product_data);
        $product->save();
        $product->saveSeo($request);
        $product->saveLocalization($request);
        $product->saveGalleries($request);

        $product->categories()->sync($product_categories);

        if(!empty($original_product['attributes'])){
            $product_attributes = [];
            foreach($original_product['attributes'] as $attribute){
                $product_attributes[] = [
                    'product_id' => $product->id,
                    'attribute_id' => $attribute['attribute_id'],
                    'attribute_value_id' => $attribute['attribute_value_id'],
                ];
            }

            $product->attributes()->createMany($product_attributes);
        }

        if(!empty($request->variations))
            $this->updateVariations($product, $request->variations);

        $product->load('seo');

        if(env('REDIS_CACHE')) {
            Redis::command('setbit', ["product_visible", $product->id, empty($product->visible) ? 0 : 1]);
            Redis::command('setbit', ["product_stock", $product->id, $product->stock > 0 ? 1 : 0]);
            Redis::command('setbit', ["product_not_stock", $product->id, $product->stock === -2 ? 1 : 0]);
            Redis::command('setbit', ["product_under_the_order", $product->id, $product->stock === -1 ? 1 : 0]);
            Redis::command('setbit', ["product_expected", $product->id, $product->stock === 0 ? 1 : 0]);
            Redis::command('zadd', ['prices', $product->price * 100, $product->id]);
            Redis::command('zadd', ['sort_priority', $product->sort_priority * 100, $product->id]);
            Redis::command('zadd', ['popularity', $product->popularity * 100, $product->id]);
            Redis::command('zadd', ['ratings', $product->ratings * 100, $product->id]);

            if(!empty($request->product_category_id)){
                foreach($request->product_category_id as $category_id){
                    Redis::command('setbit', ["category_$category_id", $product->id, 1]);
                }
            }

            if(!empty($request->product_attributes)){
                foreach($request->product_attributes as $attr){
                    Redis::command('setbit', ["attribute_".$attr['value'], $product->id, 1]);
                }
            }
        }

        Action::createEntity($product);

        return redirect('/admin/products/edit/'.$product->id)
            ->with('message-success', trans('locale.products.duplicated', ['name' => $product->name]));
    }

    /**
     * Обновление наличия товара
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStockAction(Request $request, $id){
        $user = Sentinel::check();
        if(!$user->hasAccess(['products.view'])){
            return response()->json([
                'result' => 'error',
                'message' => trans('locale.products.access_denied')
            ], 403);
        }

        $product = Product::find($id);

        if(empty($product)) {
            return response()->json([
                'result' => 'error',
                'message' => trans('locale.products.not_found')
            ], 404);
        }

        $product->stock = $request->stock;
        $product->save();

        if(env('REDIS_CACHE')) {
            Redis::command('setbit', ["product_visible", $product->id, empty($product->visible) ? 0 : 1]);
            Redis::command('setbit', ["product_stock", $product->id, $product->stock > 0 ? 1 : 0]);
            Redis::command('setbit', ["product_not_stock", $product->id, $product->stock === -2 ? 1 : 0]);
            Redis::command('setbit', ["product_under_the_order", $product->id, $product->stock === -1 ? 1 : 0]);
            Redis::command('setbit', ["product_expected", $product->id, $product->stock === 0 ? 1 : 0]);
        }

        return response()->json([
            'result' => 'success',
            'message' => trans('locale.products.stock_updated')
        ]);
    }

	public function adminUpdateVisibilityAction(Request $request, $id){
		$product = Product::find($id);
		if(empty($product)){
			return json_encode([]);
		}

        $product_data = $product->fullData();

		$product->update(['visible' => $request->stock]);

        Action::updateEntity($product, $product_data);

		return json_encode($product->toArray());
	}

    public function adminVideoReviewsAction(Request $request, $id)
    {
        $product = Product::find($id);
        if(empty($product)){
            return json_encode(['result' => 'error']);
        }

        $gallery = new Gallery();
        $gallery->saveGalleries($request, $product, ['video_reviews']);

        return response()->json([
            'result' => 'success'
        ]);
    }

    /**
     * Удаление товара
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminDeleteAction($id)
    {
        $product = Product::find($id);

        if(empty($product)) {
            return response()->json(['result' => 'error', 'message' => trans('locale.products.not_found')], 200);
        }

        Action::deleteEntity($product);

        $product->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.products.deleted_success')], 200);
    }

    /**
     * @param Attribute $attributes
     * @return string|void
     */
    public function getAttributes(Attribute $attributes, Request $request)
    {
        if(!empty($request->ids)){
            $attr = $attributes->whereIn('id', $request->ids)->get();
        }else{
            $attr = $attributes->all();
        }
        $response = [];

        if(!empty($attr)){
            foreach ($attr as $attribute) {
                $response[] = [
                    'attribute_id'    => $attribute->id,
                    'attribute_name'  => $attribute->name
                ];
            }
        }

        return json_encode($response);
    }

    /**
     * Получение списка значений переданного атрибута
     *
     * @param Attribute $attributes
     * @param Request $request
     * @return string|void
     */
    public function getAttributeValues(Attribute $attributes, Request $request)
    {
        $attribute = $attributes->find((int)$request->attribute_id);
        $response = [];

        if ($attribute !== null) {
            foreach ($attribute->values as $value) {
                $response[] = [
                    'attribute_value_id'    => $value->id,
                    'attribute_value'       => $value->name
                ];
            }
        }

        return json_encode($response);
    }

	/**
	 * Живой поиск для модулей
	 *
	 * @param Request $request
	 * @param Product $products
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function livesearch(Request $request, Product $products)
	{
		$search_text = $request->input('search');
		if(strpos($search_text, '%') === 0){
			$search_text = urlencode($search_text);
		}

		if(!empty($request->limit)){
			$limit = $request->limit;
		}else{
			$limit = 8;
		}

		// Установка текущей страницы пагинации
		$results = $products->search(trim($search_text), 1, $limit);

		foreach ($results as $result) {
			if ($result) {
				$json[] = [
					'product_id' => $result->id,
					'name'       => $result->name,
					'sku'        => $result->sku,
					'url'        => $result->link(),
					'price'      => '£'.$result->price,
					'image'      => !empty($result->image) ? $result->image->url() : '/uploads/no_image.jpg',
				];
			}
		}

		if(!empty($json)){
			return response()->json($json);
		}else{
			return response()->json([]);
		}
	}

    /**
     * Страница поиска
     *
     * @param Request $request
     * @param string $page
     * @return mixed
     */
    public function search(Request $request, $page = 'page-1')
    {
        $filter = new Filter();
        $search_text = $request->input('text');

        if(strpos($search_text, '%') === 0){
	        $search_text = urlencode($search_text);
        }

        $filter->setSearchText($search_text);

        $products = $filter->getProducts(['sort_priority', 'asc'], 20, (int)str_replace('page-', '', $page));

        return view('public.search')
            ->with('products', $products)
            ->with('recommended', Product::where('stock', '>', 0)->where('visible', 1)->orderBy('popularity', 'desc')->take(7)->get())
            ->with('search_text', $search_text);
    }

    /**
     * Валидация атрибутов товара на одинаковые значения
     *
     * @param $attributes
     * @return bool|string
     */
    public function validateAttributes($attributes){
        $attributes_error = false;

        if (!empty($attributes)) {
            foreach ($attributes as $product_attribute) {
                $product_attribute_values[] = $product_attribute['value'];
            }

            foreach (array_count_values($product_attribute_values) as $count_value) {
                if ($count_value > 1) {
                    $attributes_error = trans('locale.products.validation.duplicate_attributes');
                    break;
                }
            }
        }

        return $attributes_error;
    }

    /**
     * Добавление размеров в вариации
     *
     * @param $data
     * @return mixed
     */
    public function addSizesVariations($data){
        if(isset($data['tables']['product_attributes'])){
            $price = $data['tables']['products']['price'];
            $variations = [];
            foreach ($data['tables']['product_attributes'] as $key => $attr){
                if($attr['attribute_id'] == 7){
                    if(!empty($attr['attribute_value_id'])) {
                        $variations[] = [
                            'id' => [$attr['attribute_value_id']],
                            'price' => $price,
                            'stock' => isset($attr['with_stock']) ? $attr['with_stock'] : 1
                        ];
                    }
                    if(isset($data['tables']['product_attributes'][$key]['with_stock'])){
                        unset($data['tables']['product_attributes'][$key]['with_stock']);
                    }
                }
            }
            $data['tables']['variations'] = $variations;
        }

        return $data;
    }

    public function updatePricesAndStock($data){
        $errors = [];
        foreach($data as $key => $row){
            if(empty($row['tables']['products']['sku'])){
                $errors[] = [
                    'id' => $key+1,
                    'errors' => [trans('locale.products.validation.sku_required')]
                ];
                continue;
            }
            $product = Product::where('sku', $row['tables']['products']['sku'])->first();
            if(empty($product)){
                $errors[] = [
                    'id' => $key+1,
                    'errors' => [trans('locale.products.validation.product_not_found', ['sku' => $row['tables']['products']['sku']])]
                ];
                continue;
            }
            $old_price = $product->price;
            if($old_price == $row['tables']['products']['price']){
                $old_price = $product->old_price;
            }elseif($old_price < $row['tables']['products']['price']){
                $old_price = 0;
            }

            if(!empty($row['tables']['product_attributes'])){
                $stock = 1;
            }else{
                $stock = 0;
            }

            $product->fill([
                'price' => $row['tables']['products']['price'],
                'old_price' => $old_price,
                'stock' => $stock
            ]);

            $product->push();

            if(!empty($row['tables']['product_attributes'])){
                $ids = [];
                $product_attributes = [];
                foreach ($row['tables']['product_attributes'] as $attribute) {
                    if(!empty($attribute['attribute_value_id'])) {
                        $product_attributes[] = [
                            'product_id' => $product->id,
                            'attribute_id' => $attribute['attribute_id'],
                            'attribute_value_id' => $attribute['attribute_value_id'],
                        ];
                    }
                    if(!in_array($attribute['attribute_id'], $ids)){
                        $ids[] = $attribute['attribute_id'];
                    }
                }

                $product->attributes()->whereIn('attribute_id', $ids)->delete();
                $product->attributes()->createMany($product_attributes);
            }

            if(isset($row['tables']['variations'])){
                foreach ($row['tables']['variations'] as $key => $variation) {
                    $row['tables']['variations'][$key]['price'] = $row['tables']['products']['price'];
                }
                $this->updateVariations($product, $row['tables']['variations']);
            }
        }

        return $errors;
    }

    /**
     * Валидация импортируемых данных
     *
     * @param $prepared_data
     * @return array
     */
    public function validate_prepared_data($prepared_data){
        $products = new Product();
        $names = [];
        foreach ($products->select('name')->get() as $product){
            $names[] = $product->name;
        }
        $errors = [];
        foreach ($prepared_data as $id => $row){
            $err = [];

            if(empty($row['tables']['products']['name'])){
                $err[] = trans('locale.products.validation.empty_product_name');
            }

            foreach ($row['tables']['galleries'] as $image){
                if(empty($image['images'])){
                    $err[] = trans('locale.products.validation.unknown_image');
                }
            }

            if(isset($row['tables']['product_attributes'])){
                foreach ($row['tables']['product_attributes'] as $attribute){
                    if(empty($attribute['attribute_value_id'])){
                        $err[] = trans('locale.products.validation.unknown_attribute_value', [
                            'attribute_id' => $attribute['attribute_id']
                        ]);
                    }
                }
            }

            if(!empty($err)){
                $errors[] = [
                    'id' => $id+1,
                    'errors' => $err
                ];
            }
        }

        return $errors;
    }

    /**
     * Парсинг опций вставки
     *
     * @param $field
     * @return array|bool
     */
    public function get_field_options($field){
        $params = explode('.', $field);
        if(count($params) < 2)
            return false;
        $options = [
            'table' => $params[0],
            'field' => $params[1]
        ];
        $count = count($params);
        if($count > 2){
            for($i=2; $i<$count; $i++){
                if(strpos($params[$i], 'selector') === 0){
                    $options['selector'] = preg_replace('/selector\((.+)\)/', '$1', $params[$i]);
                }elseif(strpos($params[$i], 'attached_field') === 0){
                    if(!isset($options['attached_fields']))
                        $options['attached_fields'] = [];
                    $attached_field = explode(':', preg_replace('/attached_field\((.+)\)/', '$1', $params[$i]), 2);
                    $options['attached_fields'][$attached_field[0]] = $attached_field[1];
                }elseif($params[$i] == 'unique'){
                    $options['unique'] = true;
                }elseif($params[$i] == 'with_stock'){
                    $options['with_stock'] = 1;
                }elseif(strpos($params[$i], 'replace') === 0){
                    if(!isset($options['attached_fields']))
                        $options['attached_fields'] = [];
                    $replace = explode(':', preg_replace('/replace\((.+)\)/', '$1', $params[$i]), 3);
                    $options['replace'] = ['table' => $replace[0], 'find' => $replace[1], 'replaced' => $replace[2]];
                }elseif(strpos($params[$i], 'relations') === 0){
                    $options['relations'] = preg_replace('/relations\((.+)\)/', '$1', $params[$i]);
                }elseif(strpos($params[$i], 'load') === 0){
                    $options['load'] = explode(':', preg_replace('/load\((.+)\)/', '$1', $params[$i]), 2);
                }
            }
        }

        return $options;
    }

    /**
     * Получение одного поля таблицы по другому
     *
     * @param $data
     * @param $table
     * @param $find
     * @param $replaced
     * @return mixed
     */
    public function replace_inserted_data($data, $table, $find, $replaced){
        $model_name = 'App\Models\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', preg_replace('/s$/', '', $table))));

        if(!class_exists($model_name))
            $model_name = 'App\Models\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
        if(!class_exists($model_name) || $data == '')
            return null;

        if($table == 'categories'){
            $br = explode('>', $data);
            if(count($br) > 1){
                $table = new $model_name;
                $parent = 0;
                foreach ($br as $name){
                    $result = $table->select($replaced)->where($find, '=', trim($name))->where('parent_id', $parent)->take(1)->get()->first();
                    if(empty($result)){
                        return empty($parent) ? null : $parent;
                    }
                    $parent = $result->id;
                }

                return $result !== null ? $result->$replaced : $result;
            }
        }

        $table = new $model_name;
        $result = $table->select($replaced)->where($find, '=', trim($data))->take(1)->get()->first();

        return $result !== null ? $result->$replaced : $result;
    }

    /**
     * Страница обновления базы Redis
     *
     * @return mixed
     */
    public function adminRedisSyncAction(){
//	    Redis::command('FLUSHDB');

        return view('admin.products.redis.index')
            ->with('products_pages', ceil(Product::count()/1000))
            ->with('categories_pages', ceil(Category::count()/5))
            ->with('attributes_pages', ceil(AttributeValue::count()/5))
            ->with('sales_pages', ceil(Sale::count()/5));
    }

    private function bitmap_ids($bitmap){
        $bytes = unpack('C*', $bitmap);
        $bin = join(array_map(function($byte){
            return sprintf("%08b", $byte);
        }, $bytes));
        return array_keys(str_split($bin), 1);
    }

    /**
     * Процесс обновления базы Redis
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminRedisSyncProgress(Request $request){
        if($request->action == 'products'){
            return $this->redisProductsSync($request->page);
        }elseif($request->action == 'categories'){
            return $this->redisCategoriesSync($request->page);
        }elseif($request->action == 'attributes'){
            return $this->redisAttributesSync($request->page);
        }elseif($request->action == 'sales'){
            return $this->redisSalesSync($request->page);
        }elseif($request->action == 'search'){
            return $this->redisSearchIndex($request->page);
        }

        return response()->json(['result' => 'error']);
    }

    /**
     * Установка битмапов для фильтрируемых свойств товара
     *
     * @param $page
     * @return \Illuminate\Http\JsonResponse
     */
    private function redisProductsSync($page){
        $products = Product::select(['id', 'stock', 'visible', 'price', 'sale', 'sale_from', 'sale_to'])->limit(1000)->offset(1000 * ($page - 1))->get();

        if($page == 1){
            Redis::command('del', ['product_visible']);
            Redis::command('del', ['product_stock']);
            Redis::command('del', ['product_not_stock']);
            Redis::command('del', ['product_under_the_order']);
            Redis::command('del', ['product_expected']);
            Redis::command('del', ['product_sale']);
            Redis::command('del', ['prices']);
            Redis::command('del', ['sort_priority']);
            Redis::command('del', ['popularity']);
            Redis::command('del', ['ratings']);
        }

        $visible = ['product_visible'];
        $stock = ['product_stock'];
        $not_stock = ['product_not_stock'];
        $under_the_order = ['product_under_the_order'];
        $expected = ['product_expected'];
        $product_sale = ['product_sale'];

        $date = time();

        foreach($products as $product){
            $visible[] = 'SET';
            $visible[] = 'u1';
            $visible[] = $product->id;
            $visible[] = (int)!empty($product->visible);

            $stock[] = 'SET';
            $stock[] = 'u1';
            $stock[] = $product->id;
            $stock[] = $product->stock > 0 ? 1 : 0;

            $not_stock[] = 'SET';
            $not_stock[] = 'u1';
            $not_stock[] = $product->id;
            $not_stock[] = $product->stock === -2 ? 1 : 0;

            $under_the_order[] = 'SET';
            $under_the_order[] = 'u1';
            $under_the_order[] = $product->id;
            $under_the_order[] = $product->stock === -1 ? 1 : 0;

            $expected[] = 'SET';
            $expected[] = 'u1';
            $expected[] = $product->id;
            $expected[] = $product->stock === 0 ? 1 : 0;

            $product_sale[] = 'SET';
            $product_sale[] = 'u1';
            $product_sale[] = $product->id;
            $product_sale[] = ($product->sale === 1 && (empty($product->sale_from) || strtotime($product->sale_from) <= $date) && (empty($product->sale_to) || strtotime($product->sale_to)) >= $date) ? 1 : 0;

            Redis::command('zadd', ['prices', $product->price * 100, $product->id]);
            Redis::command('zadd', ['sort_priority', $product->sort_priority * 100, $product->id]);
            Redis::command('zadd', ['popularity', $product->popularity * 100, $product->id]);
            Redis::command('zadd', ['ratings', $product->ratings * 100, $product->id]);
        }

        Redis::command('bitfield', $visible);
        Redis::command('bitfield', $stock);
        Redis::command('bitfield', $not_stock);
        Redis::command('bitfield', $under_the_order);
        Redis::command('bitfield', $expected);
        Redis::command('bitfield', $product_sale);

        return response()->json(['result' => 'success']);
    }

    /**
     * Установка битмапов для категорий товаров
     *
     * @param $page
     * @return \Illuminate\Http\JsonResponse
     */
    private function redisCategoriesSync($page){
        $categories = Category::select(['id'])->limit(5)->offset(5 * ($page - 1))->orderBy('id')->get();
        foreach($categories as $category){
            $ids = array_merge([$category->id], $category->getChildrenCategories($category->id));
            $products = Category::select(['prod.product_id as id'])->join('product_categories AS prod', 'categories.id', '=', 'prod.category_id')->whereIn('categories.id', $ids)->get()->pluck('id')->unique()->sort()->values()->all();

	        Redis::command('del', ['category_'.$category->id]);
	        foreach(array_chunk($products, 1000) as $data){
		        $command = ['category_'.$category->id];

		        foreach($data as $product_id){
			        $command[] = 'SET';
			        $command[] = 'u1';
			        $command[] = $product_id;
			        $command[] = 1;
		        }

		        Redis::command('bitfield', $command);
	        }
        }

        return response()->json(['result' => 'success', 'ids' => $categories->pluck('id')->toArray(),  'keys' => Redis::command('keys', ['*'])]);
    }

    /**
     * Установка битмапов для атрибутов товаров
     *
     * @param $page
     * @return \Illuminate\Http\JsonResponse
     */
    private function redisAttributesSync($page){
        $values = AttributeValue::select(['id'])->limit(5)->offset(5 * ($page - 1))->get();
        foreach($values as $value){
            $products = $value->products()->select('product_id')->get()->pluck('product_id')->unique()->sort()->values()->all();

	        Redis::command('del', ['attribute_'.$value->id]);
	        foreach(array_chunk($products, 100) as $data){
		        $command = ['attribute_'.$value->id];

		        foreach($data as $product_id){
			        $command[] = 'SET';
			        $command[] = 'u1';
			        $command[] = $product_id;
			        $command[] = 1;
		        }

		        Redis::command('bitfield', $command);
	        }
        }

        return response()->json(['result' => 'success', 'keys' => Redis::command('keys', ['*'])]);
    }

    /**
     * Установка битмапов для акций товаров
     *
     * @param $page
     * @return \Illuminate\Http\JsonResponse
     */
    private function redisSalesSync($page){
        $sales = Sale::select(['id'])->limit(5)->offset(5 * ($page - 1))->get();
        foreach($sales as $sale){
            $products = $sale->products()->select('product_id')->get()->pluck('product_id')->unique()->sort()->values()->all();

	        Redis::command('del', ['sale_'.$sale->id]);
	        foreach(array_chunk($products, 100) as $data){
		        $command = ['sale_'.$sale->id];

		        foreach($data as $product_id){
			        $command[] = 'SET';
			        $command[] = 'u1';
			        $command[] = $product_id;
			        $command[] = 1;
		        }

		        Redis::command('bitfield', $command);
	        }
        }

        return response()->json(['result' => 'success', 'keys' => Redis::command('keys', ['*'])]);
    }

    private function redisSearchIndex($page){
        $items = Product::select(['id', 'external_id'])
            ->with(['localization' => function($query){
                $query->select(['localizable_id', 'value'])->whereIn('field', ['name', 'description']);
            }])
            ->take(1000)
            ->offset(1000 * ($page - 1))
            ->get();

        if($page == 1){
	        Redis::command('ZREMRANGEBYSCORE', ['words', '-inf', '+inf']);
	        Redis::command('del', ['words']);
        }

        foreach($items as $item){
            $tags = [mb_strtolower($item->external_id)];
            foreach($item->localization as $value){
                $tags = array_merge($tags, explode(' ', mb_strtolower(strip_tags($value->value))));
            }

            foreach(array_unique($tags) as $tag){
                Redis::command('zincrby', ['words:'.$tag, 1, $item->id]);
            }
        }

        return response()->json(['result' => 'success']);
    }

    /**
     * Обновление вариаций
     *
     * @param $product
     * @param $variations
     */
    public function updateVariations($product, $variations){
        $current_variations = $product->variations;
        $add = [];
        $update = [];
        $remove = $current_variations->pluck(['id'])->toArray();
        if(!empty($variations)){
            foreach ($variations as $variation){
                $add_var = true;
                foreach ($current_variations as $var){
                    if(empty($variation['id'])){
                        $add_var = false;
                        break;
                    }
                    if($var->price == $variation['price']){
                        if(empty(array_diff($variation['id'], $var->attribute_values->pluck(['id'])->toArray()))){
                            $add_var = false;
                            unset($remove[array_search($var->id,$remove)]);
                            if($var->stock != $variation['stock']){
                                $update[$var->id] = $variation;
                            }
                            break;
                        }
                    }
                }
                if($add_var){
                    $add[] = $variation;
                }
            }
        }
        foreach ($remove as $id){
            $v = new Variation();
            $v->find($id)->update(['product_id' => null]);
        }
        foreach ($add as $variation){
            if(!empty($variation['price']) && !empty($variation['id'])){
                $v = new Variation();
                $id = $v->insertGetId(['product_id' => $product->id, 'price' => $variation['price'], 'stock' => $variation['stock']]);
                $v->find($id)->attribute_values()->attach($variation['id']);
            }
        }
        foreach ($update as $id => $variation){
            $v = Variation::where('id', $id);
            $v->update(['stock' => $variation['stock']]);
        }
    }

    public function popupAction(Request $request){
        $product = Product::find($request->id);

        $attributes = [];
        foreach($product->attributes()->with('info.localization', 'value.localization')->get() as $attribute){
            if($attribute->info->visible){
                if(!isset($attributes[$attribute->info->name]))
                    $attributes[$attribute->info->name] = [];

                $attributes[$attribute->info->name][] = $attribute->value->name;
            }
        }

        $variations = $product->variations_attributes();

        return view('public.layouts.product_popup')
            ->with('product', $product)
            ->with('attributes', $attributes)
            ->with('variations_prices', $variations['variations_prices'])
            ->with('variations', $variations['variations_attrs'])
            ->with('selected_variation_attributes', $variations['selected_variation_attributes']);
    }
}
