<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\Helper;
use App\Models\AttributeValue;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Redirect;
use App\Models\Product;
use App\Models\Filter;
use App\Models\Action;
use App\Models\Sale;
use App\Models\User;

class CategoriesController extends Controller
{
    /**
     * Каталог товаров
     *
     * @param $data
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
	public function showAction($data){
        if(empty($data->seo->seotable)){
            abort( 404 );
        }
		$filter = new Filter();

        $filter->setCategory($data->seo->seotable);

        if(isset($data->request['text'])){
            $filter->setSearchText($data->request->input('text'));
        }

		if(isset($data->params)){
			foreach($data->params as $param){
				if(!empty($param))
                    $filter->setParam($param);
			}
		}

		if(!empty($filter->getUndefined())){
			abort( 404 );
		}

        $orders = [
            'priority-asc' => ['sort_priority', 'asc'],
            'popularity-desc' => ['popularity', 'desc'],
            'rating-desc' => ['rating', 'desc'],
            'created-desc' => ['created', 'desc'],
            'price-asc' => ['price', 'asc'],
            'price-desc' => ['price', 'desc'],
        ];

        $sale_products = null;
        $sale = Sale::first();
        if(!empty($sale))
            $sale_products = $sale->products;

        $viewed = json_decode($data->request->cookie('viewed'), true);
        $products = new Product();

        $attributes = $filter->getFilter();
        if(!empty($attributes)){
            if(count($attributes) > 1
                || (!empty($val_ids) && count($val_ids) > 1)
                || !empty($data->request->order)){
                $data->seo->robots = 'noindex, nofollow';
            }
        }

        if($filter->with_price_filter || $filter->with_stock_filter){
            $data->seo->robots = 'noindex, nofollow';
        }

        if(!empty($data->request->order) || $filter->isSale()){
            $data->seo->canonical = str_replace('/sale', '', $data->request->url());
        }

        
		return view('public.catalog')
			->with('seo', $data->seo)
			->with('category', $data->seo->seotable)
			->with('subcategories', $data->seo->seotable->children)
			->with('products', $filter->getProducts(isset($orders[$data->request->order]) ? $orders[$data->request->order] : ['sort_priority', 'asc']))
			->with('filter', $filter->getFilterAttributes(true))
			->with('selected_filters', $filter->getSelectedFilters())
            ->with('price', $filter->getPriceSlider())
            ->with('view', empty($data->request->view) || !in_array($data->request->view, ['grid', 'list']) ? 'grid' : $data->request->view)
			->with('path', $data->request->path())
            ->with('sale', $sale)
            ->with('viewed', !is_null($viewed) ? $products->getProducts($viewed) : null)
            ->with('sale_products', $sale_products)
            ->with('categories', Category::where('status', 1)->get())
            ->with('effects', AttributeValue::where('attribute_id', 1)->get())
            ->withShortcodes();
	}

    /**
     * Список категорий
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction(){
        return view('admin.products.categories.index')
            ->with([
                'localization' => json_encode(['datatable' => trans('datatable')])
            ]);
    }

    /**
     * Фильтр категорий
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }

        $query = Category::select('categories.*');

        if($request->has('search.value')){
            $locale = App::getLocale();
            $text = $request->search['value'];
            $query->leftJoin('localization', function($query) use($text, $locale){
                $query->on('categories.id', '=', 'localization.localizable_id')
                    ->where('field', 'name')
                    ->where('localizable_type', 'Categories')
                    ->where('language', $locale)
                    ->where('value', 'like', '%'.$text.'%');
            });
        }

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

        $categories = $query->get();

        $data = [];
        foreach($categories as $category){
            $actions = [];
            if($user->hasAccess(['categories.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/products/categories/edit/'.$category->id)
                ];
            }
            if($user->hasAccess(['categories.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $category->id,
                    'name' => $category->name
                ];
            }

            $data[] = [
                'id' => $category->id,
                'image' => !empty($category->image) ? $category->image->url([64, 64]) : null,
                'name' => ['link' => $category->link(), 'name' => $category->name],
                'status' => ['id' => $category->id, 'status' => (bool)$category->status],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Category::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

	/**
	 * Создание категории
	 *
	 * @param Request $request
	 * @param Category $categories
	 *
	 * @return $this
	 */
    public function adminStoreAction(Request $request, Category $categories){
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ], [
            'name.required' => trans('validation.required', ['attribute' => trans('locale.Name')])
        ]);

        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $data = [
            'file_id' => null,
            'parent_id' => null,
            'status' => 0
        ];
        $data['slug'] = str_replace(['/', '-', '_'], '', $request->url);
        $categories->fill($data);
        $categories->slug = Str::slug(mb_strtolower(Helper::translit($request->name)));
        $categories->save();
        $request->merge([
            'name_'.Config::get('app.main_locale') => $request->name
        ]);
	    $categories->saveSeo($request);
	    $categories->saveLocalization($request);

        Action::createEntity($categories);

        return response()->json(['result' => 'success', 'redirect' => '/admin/products/categories/edit/'.$categories->id]);
    }

	/**
	 * Страница изменения категории
	 *
	 * @param Request $request
	 * @param $id
	 *
	 * @return mixed
	 */
    public function adminEditAction(Request $request, $id){
        $category = Category::find($id);
        $attributes = $category->attributes->pluck('id')->toArray();

        if(!empty($request->prev)){
	        $prev = $request->prev;
        }else{
	        $prev = app('url')->previous();
        }

        $all_categories = $category->getTreeList($id);

        return view('admin.products.categories.edit')
            ->with('attributes', Attribute::where('is_filter', 1)->get())
            ->with('related_attributes', $attributes)
            ->with('category', $category)
            ->with('prev', $prev)
            ->with('categories', $all_categories)
            ->with('languages', Config::get('app.locales_names'))
	        ->with('editors', Helper::localizationFields(['body', 'seo_description']))
	        ->with('seo', $category->seo);
    }

    /**
     * Обновление категории
     *
     * @param Request $request
     * @param Category $categories
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateAction(Request $request, Category $categories, $id){
        $rules = [];
        $messages = [];
        foreach(Helper::localizationFields(['name']) as $key){
            $rules[$key] = 'required';
            $messages[$key.'.required'] = trans('validation.required', ['attribute' => trans('locale.Name')]);
        }
        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->messages(), 'message' => trans('locale.Form validation error')], 200);
        }

        $category = $categories->find($id);

        if(empty($category)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Category not found')], 200);
        }

        $category_data = $category->fullData();

        $category->parent_id = $request->parent_id;
	    $category->file_id = !empty($request->file_id) ? $request->file_id : null;
        $category->status = !empty($request->status) ? $request->status : 0;
        $category->save();
	    $category->saveLocalization($request);
        $category->saveGalleries($request);
        $category->attributes()->sync($request->related_attribute_ids);

        Action::updateEntity($categories->find($id), $category_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
    }

    /**
     * Обновление SEO данных категории
     *
     * @param Request $request
     * @param Category $categories
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSeoAction(Request $request, Category $categories, $id){
        $category = $categories->find($id);

        if(empty($category)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Category not found')], 200);
        }

        $seo_data = !empty($category->seo) ? $category->seo->fullData() : [];
        $category->saveSeo($request);
        $category->load('seo');
        $category->slug = Str::slug(str_replace(['/', '_'], '', $category->seo->url));

        Action::updateEntity($category->seo, $seo_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
    }

    /**
     * Обновление статуса категории
     *
     * @param Request $request
     * @param Category $categories
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStatusAction(Request $request, Category $categories, $id){
        $category = $categories->find($id);

        if(empty($category)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Category not found')], 200);
        }
        $category_data = $category->getActionData();
        $category->status = (int)$request->status;
        $category->save();

        Action::updateEntity($category, $category_data);

        if($category->status)
            return response()->json(['result' => 'success', 'message' => trans('locale.Category enabled')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.Category disabled')], 200);
    }

    /**
     * Удаление категории
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminDeleteAction($id){
        $category = Category::find($id);

        if(empty($category)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Category not found')], 200);
        }

        // Сохранение действия
        Action::deleteEntity($category);

        $name = $category->name;
        // Удаление переводов
        $category->localization()->delete();
        $url = $category->seo->url;
        Redirect::where('new_url', $url)->delete();
        // Удаление сео записи
        $category->seo()->delete();
        // Удаление фильтров
        $category->attributes()->detach();
        // Перенос товаров в родительскую категорию
        if(!empty($category->parent_id)){
            $category->parent->products()->syncWithoutDetaching($category->products->pluck('id')->toArray());
        }
        // Открепление товаров
        $category->products()->detach();
        // Перенос дочерних категорий в родительскую категорию
        foreach($category->children as $children){
            $children->parent_id = !empty($category->parent_id) ? $category->parent_id : null;
            $children->save();
        }
        // Удаление галлерей
        $category->galleries()->delete();
        // Удаление категории
        $category->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.Category :name deleted', ['name' => $name])], 200);
    }

    /**
     * Получение дочерних категорий
     *
     * @param $id
     * @param Category $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminChildrenAction($id, Category $category){
        $data = [];
        $categories = $category->select(['id'])
            ->with(['localization', 'children'])
            ->where('parent_id', $id)
            ->get();
        $data['categories'] = [];
        foreach($categories as $category){
            $data['categories'][] = [
                'id' => $category->id,
                'name' => $category->name,
                'has_children' => (bool)$category->children->count()
            ];
        }
        if($id > 0){
            $products = $category->find($id)->products()->select('product_id as id', 'sku', 'name', 'price', 'file_id', 'stock')->with('image')->get();
            $products_arr = [];
            foreach ($products as $product){
                $products_arr[] = [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image->url([100, 100]),
                    'stock' => $product->stock
                ];
            }
            $data['products'] = $products_arr;
        }

        return response()->json($data);
    }

    /**
     * Поиск категорий по названию
     *
     * @param Request $request
     * @param Category $categories
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminLivesearchAction(Request $request, Category $categories){
        $data = [];
        foreach(
            $categories
                ->select(['categories.id', 'localization.value as name'])
                ->leftJoin('localization', function($join){
                    $join->on('categories.id', '=', 'localization.localizable_id')
                        ->where('localizable_type', 'Categories')
                        ->where('field', 'name')
                        ->where('language', 'ru');
                })
                ->where('localization.value', 'like', '%'.$request->search.'%')
                ->get() as $category){
            $data[] = [
                'id' => $category->id,
                'name' => $category->name
            ];
        }

        return response()->json($data);
    }

    /**
     * Древо категорий
     *
     * @param Request $request
     * @param Category $categories
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminTreeAction(Request $request, Category $categories){
        return response()->json([
            'result' => 'success',
            'tree' => $this->getTreeviewList($categories->get_root_categories(null), !empty($request->with_products))
        ]);
    }

    /**
     * Данные для древовидного отображения категорий
     *
     * @param $tree
     * @return array
     */
    public function getTreeviewList($tree, $with_products = false){
        $list = [];

        if($tree->count()){
            foreach($tree as $i => $category){
                $name = $category->name;
                $list[$i] = [
                    'text' => $name,
                    'selectable' => false,
                    'icon' => '',
                    'customClass' => $with_products ? 'no-checkbox' : '',
                    'tags' => ['id' => $category->id],
                    'state' => [
                        'expanded' => false
                    ]
                ];
                if($category->children->count()){
                    $list[$i]['nodes'] = $this->getTreeviewList($category->children, $with_products);
                }
                if($with_products && $category->products->count()){
                    foreach($category->products as $product){
                        $list[$i]['nodes'][] =  [
                            'text' => '<span class="icon check-icon bx bxs-minus-square mr-1"></span><img class="rounded-circle mr-1" width="24" height="24" src="'.$product->image->url().'">'.$product->name,
                            'selectable' => false,
                            'icon' => '',
                            'tags' => ['id' => $product->id],
                            'state' => [
                                'expanded' => false
                            ]
                        ];
                    }
                }
            }
        }

        return $list;
    }
}
