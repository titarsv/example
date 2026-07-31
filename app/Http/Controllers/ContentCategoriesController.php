<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ContentCategory;
use App\Models\Action;
use App\Models\User;
use App\Helpers\Helper;

class ContentCategoriesController extends Controller
{
    /**
     * Список статей категории
     *
     * @param $data
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showAction($data){
        if(empty($data->seo->seotable)){
            abort( 404 );
        }

        $current_category = $data->seo->seotable;

        if(!empty($data->params)){
            foreach($data->params as $param){
                if(substr($param, 0, 5) === 'page-'){
                    $page = str_replace('page-', '', $param);
                    Paginator::currentPageResolver(function() use($page){
                        return $page;
                    });
                }
            }
        }

        $articles = $current_category->articles()->where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->with('categories')
            ->paginate(5);

        $latest_articles = $current_category->articles()->where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->with('categories')
            ->paginate(4);

        $data->seo->robots = 'noindex, follow';

        return view('public.blog')
            ->with('seo', $data->seo)
            ->with('categories', ContentCategory::where('status', 1)->get())
            ->with('current_category', $current_category)
            ->with('latest_articles', $latest_articles)
            ->with('articles', $articles);
    }

    /**
     * Список категорий
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction(){
        return view('admin.content_categories.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable'), 'js_messages' => trans('js_messages')])]);
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

        $query = ContentCategory::select('content_categories.*');

        if($request->has('search.value')){
            $locale = App::getLocale();
            $text = $request->search['value'];
            $query->leftJoin('localization', function($query) use($text, $locale){
                $query->on('content_categories.id', '=', 'localization.localizable_id')
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
            if($user->hasAccess(['content_categories.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/content/categories/edit/'.$category->id)
                ];
            }
            if($user->hasAccess(['content_categories.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $category->id,
                    'name' => $category->name
                ];
            }

            $data[] = [
                'id' => $category->id,
                'name' => ['link' => $category->link, 'name' => $category->name],
                'sort_order' => $category->sort_order,
                'status' => ['id' => $category->id, 'status' => (bool)$category->status],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => ContentCategory::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }


    /**
     * Создание категории
     *
     * @param Request $request
     * @param ContentCategory $categories
     *
     * @return $this
     */
    public function adminStoreAction(Request $request, ContentCategory $categories){
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ], [
            'name.required' => trans('locale.This field must be filled!')
        ]);

        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $data = [
            'color' => $request->color,
            'file_id' => null,
            'sort_order' => 0,
            'parent_id' => null,
            'status' => 0
        ];
        $data['slug'] = str_replace(['/', '-', '_'], '', $request->url);
        $categories->fill($data);
        $categories->slug = Str::slug(mb_strtolower(Helper::translit($request->name)));
        $categories->save();
        $request->merge([
            'name_'.Config::get('app.main_locale') => $request->name,
        ]);
        $categories->saveSeo($request);
        $categories->saveLocalization($request);

        Action::createEntity($categories);

        return response()->json(['result' => 'success', 'redirect' => '/admin/content/categories/edit/'.$categories->id]);
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
        $category = ContentCategory::find($id);

        if(!empty($request->prev)){
	        $prev = $request->prev;
        }else{
	        $prev = app('url')->previous();
        }

        $all_categories = $category->getTreeList($id);

        return view('admin.content_categories.edit')
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
	 * @param $id
	 * @param ContentCategory $categories
	 *
	 * @return $this
	 */
    public function adminUpdateAction(Request $request, ContentCategory $categories, $id){
        $rules = [];
        $messages = [];
        foreach(Helper::localizationFields(['name']) as $key){
            $rules[$key] = 'required';
            $messages[$key.'.required'] = trans('locale.Please fill in this field');
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

        $category->color = $request->color;
        $category->parent_id = $request->parent_id;
        $category->file_id = !empty($request->file_id) ? $request->file_id : null;
        $category->sort_order = !empty($request->sort_order) ? $request->sort_order : 0;
        $category->status = !empty($request->status) ? $request->status : 0;
        $category->save();
        $category->saveLocalization($request);

        Action::updateEntity($categories->find($id), $category_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
    }

    /**
     * Обновление SEO данных категории
     *
     * @param Request $request
     * @param ContentCategory $categories
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSeoAction(Request $request, ContentCategory $categories, $id){
        $category = $categories->find($id);

        if(empty($category)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Category not found')], 200);
        }

        $seo_data = $category->seo->fullData();
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
     * @param ContentCategory $categories
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStatusAction(Request $request, ContentCategory $categories, $id){
        $category = $categories->find($id);

        if(empty($category)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Category not found')], 200);
        }
        $category_data = $category->fullData();
        $category->status = (int)$request->status;
        $category->save();

        Action::updateEntity($categories->find($id), $category_data);

        if($category->status)
            return response()->json(['result' => 'success', 'message' => trans('locale.Category enabled')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.Category disabled')], 200);
    }

    /**
     * Удаление категории
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminDestroyAction($id){
        $category = ContentCategory::find($id);

        Action::deleteEntity($category);

        if(!empty($category->seo)){
            $category->seo->delete();
        }
        $category->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.messages.category_deleted', ['name' => $category->name])], 200);
    }

    public function adminChildrenAction($id, ContentCategory $category){
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
            $products = $category->find($id)->products()->select('product_id as id', 'sku', 'name', 'price', 'file_id')->with('image')->get();
            $products_arr = [];
            foreach ($products as $product){
                $products_arr[] = [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image->url([100, 100])
                ];
            }
            $data['products'] = $products_arr;
        }

        return response()->json($data);
    }

    public function adminLivesearchAction(Request $request, ContentCategory $categories){
        $data = [];
        foreach(
            $categories
                ->select(['categories.id', 'localization.value as name'])
                ->leftJoin('localization', function($join){
                    $join->on('categories.id', '=', 'localization.localizable_id')
                        ->where('localizable_type', 'Categories')
                        ->where('field', 'name')
                        ->where('language', 'en');
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
}
