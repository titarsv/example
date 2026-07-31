<?php
namespace App\Http\Controllers;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\ContentCategory;
use App\Models\Redirect;
use App\Models\Action;
use App\Models\Blog;
use App\Models\User;
class BlogController extends Controller
{
    public $articles;
    public $users;
    public $user;
    protected $rules = [
        'title' => 'required|unique:blog'
    ];
    protected $messages = [];

    public function __construct(Blog $articles, User $users){
        $this->articles = $articles;
        $this->users = $users;
        $this->user = Sentinel::getUser();

        $this->messages = [
            'title.required' => trans('validation.field_required'),
            'title.unique' => trans('validation.field_unique')
        ];
    }

    /**
     * Список статей
     *
     * @param $data
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function indexAction($data){
        $latest_articles = $this->articles->where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->with('categories')
            ->paginate(4);

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

        $articles = $this->articles->where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->with('categories')
            ->paginate(5);

        return view('public.blog')
            ->with('seo', $data->seo)
            ->with('categories', ContentCategory::where('status', 1)->get())
            ->with('latest_articles', $latest_articles)
            ->with('articles', $articles);
    }

    /**
     * Просмотр статьи
     *
     * @param $data
     * @return mixed
     */
    public function showAction($data){
        $article = $data->seo->seotable;

        if(empty($article->status)){
            abort(404);
        }

        $article->increment('views', 1);

        $latest_articles = $this->articles->where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->with('categories')
            ->paginate(4);

        return view('public.article')
            ->with('article', $article)
            ->with('seo', $data->seo)
            ->with('latest_articles', $latest_articles)
            ->with('prev', $this->articles->where('id', '<', $article->id)->orderBy('id', 'desc')->first())
            ->with('next', $this->articles->where('id', '>', $article->id)->orderBy('id', 'asc')->first())
            ->withShortcodes();
    }

    /**
     * Страница со списком статей
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction(){
        return view('admin.blog.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable'), 'js_messages' => trans('js_messages')])]);
    }

    /**
     * Фильтр статей
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $query = Blog::select('blog.*');
        if($request->has('search.value')){
            $locale = App::getLocale();
            $text = $request->search['value'];
            $query->leftJoin('localization', function($query) use($text, $locale){
                $query->on('blog.id', '=', 'localization.localizable_id')
                    ->where('field', 'name')
                    ->where('localizable_type', 'Blog')
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

        $articles = $query->get();

        $data = [];
        foreach($articles as $article){
            $actions = [];
            if($this->user->hasAccess(['articles.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/articles/edit/'.$article->id)
                ];
            }
            if($this->user->hasAccess(['articles.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $article->id,
                    'name' => $article->name
                ];
            }

            $data[] = [
                'id' => $article->id,
                'name' => ['link' => $article->link(), 'name' => $article->name],
                'image' => !empty($article->image) ? $article->image->url([64, 64]) : null,
                'status' => ['id' => $article->id, 'status' => (bool)$article->status],
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Blog::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    /**
     * Создание статьи
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function adminStoreAction(Request $request){
        $validator = Validator::make($request->all(), ['name' => 'required'], ['name.required' => trans('validation.field_required')]);

        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->errors()]);
        }

        $rd = new Request();
        $rd->merge(['name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => $request->name]);

        $user_id = $this->user->id;

        $id = $this->articles->insertGetId(['user_id' => $user_id, 'status' => 0, 'image_id' => null, 'reading_time' => null]);
        $article = $this->articles->find($id);
        $article->saveSeo($rd);
        $article->saveLocalization($rd);

        Action::createEntity($this->articles->find($id));

        return response()->json(['result' => 'success', 'redirect' => '/admin/articles/edit/'.$id]);
    }

    /**
     * Страница редактирования статьи
     *
     * @param $id
     * @return mixed
     */
    public function adminEditAction($id){
        $article = $this->articles->findOrFail($id);

        $categories = [];
        if(!empty($article->categories)){
            foreach($article->categories as $category){
                $categories[] = $category->id;
            }
        }

        $authors = [];
        foreach(User::join('role_users', function ($join) {
            $join->on('users.id', '=', 'role_users.user_id')
                ->whereIn('role_users.role_id', [1,2,3,4]);
        })->get() as $user){
            $authors[] = (object)[
                'value' => $user->id,
                'name' => $user->first_name . ' '. $user->last_name,
            ];
        }

        return view('admin.blog.edit')
            ->with('categories', ContentCategory::getSelect())
            ->with('added_categories', $categories)
            ->with('article', $article)
            ->with('seo', $article->seo)
            ->with('authors', $authors)
            ->with('editors', Helper::localizationFields(['body', 'seo_description']))
            ->with('languages', Config::get('app.locales_names'));
    }

    /**
     * Обновление статьи
     *
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function adminUpdateAction($id, Request $request){
        $rules = [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => 'required',
            'body'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '') => 'required'
        ];
        $messages = [
            'name'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '').'.required' => trans('validation.field_required'),
            'body'.(count(Config::get('app.locales')) > 1 ? '_'.Config::get('app.main_locale') : '').'.required' => trans('validation.body_required')
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails()){
            return response()->json(['result' => 'error', 'errors' => $validator->messages(), 'message' => trans('messages.form_validation_error')], 200);
        }

        $article = $this->articles->find($id);

        if(empty($article)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.article_not_found')], 200);
        }

        $article_data = $article->fullData();

        $article->fill($request->only(['status', 'user_id', 'image_id', 'reading_time', 'created_at']));
        $article->save();
        $article->saveLocalization($request);
        $article->categories()->sync($request->category_id);

        Action::updateEntity($this->articles->find($id), $article_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.messages.changes_saved')], 200);
    }

    /**
     * Обновление SEO данных статьи
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSeoAction(Request $request, $id){
        $article = $this->articles->find($id);

        if(empty($article)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.article_not_found')], 200);
        }

        $seo_data = $article->seo->fullData();
        $article->saveSeo($request);
        $article->load('seo');
        $article->slug = Str::slug(str_replace(['/', '_'], '', $article->seo->url));

        Action::updateEntity($article->seo, $seo_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.messages.changes_saved')], 200);
    }

    /**
     * Обновление статуса статьи
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStatusAction(Request $request, $id){
        $article = $this->articles->find($id);

        if(empty($article)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.article_not_found')], 200);
        }
        $article_data = $article->fullData();
        $article->status = (int)$request->status;
        $article->save();

        Action::updateEntity($article->find($id), $article_data);

        if($article->status)
            return response()->json(['result' => 'success', 'message' => trans('locale.messages.article_enabled')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.messages.article_disabled')], 200);
    }

    /**
     * Удаление статьи
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function adminDestroyAction($id){
        $article = $this->articles->find($id);

        if(empty($article)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.article_not_found')], 200);
        }

        // Сохранение действия
        Action::deleteEntity($article);

        $name = $article->name;
        // Удаление переводов
        $article->localization()->delete();
        $url = $article->seo->url;
        Redirect::where('new_url', $url)->delete();
        // Удаление сео записи
        $article->seo()->delete();
        // Удаление статьи
        $article->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.messages.article_removed', ['name' => $name])], 200);
    }
}
