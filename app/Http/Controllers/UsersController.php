<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Nekhbet\LaravelGettext\Facades\LaravelGettext;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\Models\SiteReview;
use App\Models\Setting;
use App\Models\Review;
use App\Models\Action;
use App\Models\Order;
use App\Models\User;
use App\Models\Newpost;
use App\Models\Justin;

class UsersController extends Controller
{
    public $settings;
    protected $modules = [];
    protected $socials = [];
    protected $rules = [];
    protected $messages = [];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->modules = [
            'orders' => [
                'name' => trans('locale.Orders'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'products' => [
                'name' => trans('locale.Products'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'attributes' => [
                'name' => trans('locale.Attributes'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'categories' => [
                'name' => trans('locale.Product Categories'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'sales' => [
                'name' => trans('locale.Promotions'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'metadata' => [
                'name' => trans('locale.Metadata Generation'),
                'permissions' => [
                    'read', 'write'
                ]
            ],
            'coupons' => [
                'name' => trans('locale.Coupons'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'exports' => [
                'name' => trans('locale.Exports'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'imports' => [
                'name' => trans('locale.Imports'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'users' => [
                'name' => trans('locale.Users'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'reviews' => [
                'name' => trans('locale.Reviews'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'pages' => [
                'name' => trans('locale.Pages'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'blocks' => [
                'name' => trans('locale.Blocks'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'articles' => [
                'name' => trans('locale.Articles'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'content_categories' => [
                'name' => trans('locale.Article Categories'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
//            'news' => [
//                'name' => trans('locale.News'),
//                'permissions' => [
//                    'read', 'write', 'create', 'delete'
//                ]
//            ],
            'seo' => [
                'name' => trans('locale.SEO'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'redirects' => [
                'name' => trans('locale.Redirects'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'media' => [
                'name' => trans('locale.Media'),
                'permissions' => [
                    'read', 'write', 'create', 'delete'
                ]
            ],
            'settings' => [
                'name' => trans('locale.Settings'),
                'permissions' => [
                    'read', 'write'
                ]
            ],
            'cache' => [
                'name' => trans('locale.Cache'),
                'permissions' => [
                    'write'
                ]
            ],
            'redis' => [
                'name' => trans('locale.Redis'),
                'permissions' => [
                    'write'
                ]
            ]
        ];

        $this->socials = [
            'Twitter',
            'Facebook',
            'LinkedIn',
            'Instagram',
            'Viber',
            'Telegram',
            'Pinterest'
        ];

        $this->rules = [
//            'email' => 'required|unique:users',
            'first_name' => 'required',
            'last_name' => 'required'
        ];

        $this->messages = [
            'email.required' => trans('locale.This field is required'),
            'email.unique' => trans('locale.Field must be unique'),
            'first_name.required' => trans('locale.This field is required'),
            'last_name.required' => trans('locale.This field is required')
        ];
    }

    public function showAction($data){
        $author = $data->seo->seotable;

        return view('public.author')
            ->with('author', $author)
            ->with('articles', $author->blog()->take(3)->get())
            ->with('seo', $data->seo);
    }

    /**
     * Список пользователей в панели администратора
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function adminIndexAction()
    {
        $roles = Sentinel::getRoleRepository()->all();

        return view('admin.users.index', [
            'roles' => $roles,
            'localization' => json_encode(['datatable' => trans('datatable')])
        ]);
    }

    /**
     * Подгрузка пользователей
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $query = User::select(['users.*', 'roles.name as role', 'activations.completed as verified'])
            ->leftJoin('role_users', 'users.id', '=', 'role_users.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('activations', 'activations.user_id', '=', 'users.id');

        if($request->has('search.value')){
            $text = $request->search['value'];
            $query->where(function($query) use($text){
                $query->where('email', 'like', '%'.$text.'%')
                    ->orWhere('first_name', 'like', '%'.$text.'%')
                    ->orWhere('last_name', 'like', '%'.$text.'%');
            });
        }

        foreach($request->columns as $column){
            if($column['search']['value'] !== null){
                $query->where($column['name'], $column['search']['value']);
            }
        }

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'], $order['dir']);
            }
        }

        $records_filtered = $query->count();

        $users = $query->offset($request->start)
            ->limit($request->length)
            ->get();

        $data = [];
        foreach($users as $user){
            $data[] = [
                'id' => $user->id,
                'name' => ['link' => asset('admin/users/show/'.$user->id), 'name' => $user->name],
                'email' => $user->email,
                'activity' => !empty($user->activity) ? $user->activity->format('d/m/Y') : $user->created_at->format('d/m/Y'),
                'verified' => (bool)$user->verified,
                'role' => $user->role,
                'status' => (bool)$user->status,
                'actions' => [
                    [
                        'type' => 'edit',
                        'link' => asset('admin/users/edit/'.$user->id)
                    ]
                ]
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => User::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    public function adminCreateAction(){
        $roles = Sentinel::getRoleRepository()->all();

        return view('admin.users.create')
            ->with('roles', $roles);
    }

    public function adminStoreAction(Request $request){
        $rules = $this->rules;

//        $rules['email'] = 'required|unique:users,email';
        $rules['first_name'] = 'required';
        $rules['last_name'] = 'required';

        $validator = Validator::make($request->all(), $rules, $this->messages);

        if($validator->fails()){

            if($request->ajax()){
                return response()->json([
                    'result' => 'error',
                    'errors' => $validator->messages()->toJson()
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('message-error', trans('locale.Saving failed! Please check the form for errors!'))
                ->withErrors($validator);
        }

        $data = $request->except(['role']);

        if(empty($data['email'])){
            $email = strtolower(translit($request->first_name.'_'.$request->last_name));
            $i = 0;
            while(User::where('email', $email.($i ? '_'.$i : '').'@proper-loud.uk')->first()){
                $i++;
            }
            $data['email'] = $email.($i ? '_'.$i : '').'@proper-loud.uk';
        }

        $data['permissions'] = [];

        $credentials = [
            'email'    => $data['email'],
            'password' => password_hash(rand(100000, 999999), PASSWORD_DEFAULT)
        ];

        $u = Sentinel::registerAndActivate($credentials);
        $user_id = $u->id;

        $user = User::find($user_id);
        $user->update($data);

        $role = Sentinel::findRoleBySlug($request->role);
        $role->users()->attach($user);

        return response()->json([
            'result' => 'success',
            'message' => str_replace(':name', $user->name, trans('locale.User profile created successfully')),
            'redirect' => '/admin/users/edit/'.$user_id
        ]);
    }

    /**
     * Просмотр профиля пользователя
     *
     * @param $id
     * @return mixed
     */
    public function adminShowAction($id)
    {
        $user = User::select(['users.*', 'roles.name as role', 'activations.completed as verified'])
            ->leftJoin('role_users', 'users.id', '=', 'role_users.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'role_users.role_id')
            ->leftJoin('activations', 'activations.user_id', '=', 'users.id')
            ->where('users.id', $id)
            ->withCount('orders')
            ->withCount('reviews')
            ->withCount('shopreviews')
            ->first();

        $orders = Order::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return view('admin.users.show')
            ->with('user', $user)
            ->with('modules', $this->getUserModulesPermissions($user))
            ->with('orders', $orders);
    }

    /**
     * Редактирование профиля пользователя
     *
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function adminEditAction($id)
    {
        $locale = session()->get('locale');
        if(!empty($locale)) {
            config()->set('app.main_locale', $locale);
            app()->setLocale($locale);
        }else{
            $locale = app()->getLocale();
        }

        $user = User::find($id);
        $roles = Sentinel::getRoleRepository()->all();

        return view('admin.users.edit')
            ->with('user', $user)
            ->with('modules', $this->getUserModulesPermissions($user))
            ->with('roles', $roles)
            ->with('socials', $this->socials)
            ->with('seo', $user->seo)
            ->with('locale', $locale)
            ->with('languages', config('app.locales_names'))
            ->with('languages_names', config('app.languages_names'))
            ->with('editors', Helper::localizationFields(['seo_description']));
    }

    /**
     * Обновление ключевых данных профиля
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function adminUpdateProfileAction(Request $request, $id = null){
        $locale = session()->get('locale');
        if(!empty($locale)) {
            config()->set('app.main_locale', $locale);
            app()->setLocale($locale);
        }

        $rules = $this->rules;

        $rules['email'] = 'required|unique:users,email,' . $id . '';
        $rules['first_name_'.config()->get('app.main_locale')] = 'required';
        $rules['last_name_'.config()->get('app.main_locale')] = 'required';

        if(empty($id)){
            $user = Sentinel::getUser();
            $user = User::find($user->id);
            $rules['email'] = 'required|unique:users,email,' . $user->id . '';
        }else{
            $user = User::find($id);
        }

        $validator = Validator::make($request->all(), $rules, $this->messages);

        if($validator->fails()){
            if($request->ajax()){
                return response()->json([
                    'result' => 'error',
                    'errors' => $validator->messages()->toJson()
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('message-error', trans('locale.Saving failed! Please check the form for errors!'))
                ->withErrors($validator);
        }

        $user_data = $user->fullData();

        if(!empty($id)){
            if(!isset($user->roles->first()->slug) || $user->roles->first()->slug != $request->role) {
                $old_role = Sentinel::findRoleBySlug($user->roles->first()->slug);
                $old_role->users()->detach($user);
                $role = Sentinel::findRoleBySlug($request->role);
                $role->users()->attach($user);

                $user->permissions = null;
            }else{
                $permissions = [];
                foreach((array)$request->permissions as $module => $module_permissions){
                    foreach($module_permissions as $permission => $val){
                        $permissions[$module.'.'.$permission] = !empty($val);
                    }
                }

                $user->permissions = $permissions;
            }
        }

        $user->fill($request->only(['email', 'status', 'subscribe']));
        $user->first_name = $request->{'first_name_'.config()->get('app.main_locale')};
        $user->last_name = $request->{'last_name_'.config()->get('app.main_locale')};
        $user->save();
        $user->saveLocalization($request);

        Action::updateEntity(User::find($user->id), $user_data);

        if($request->ajax()){
            return response()->json([
                'result' => 'success',
                'message' => 'Профиль пользователя ' . $user->first_name . ' успешно обновлен.'
            ]);
        }

        return redirect(!empty($id) ? '/admin/users/edit/' . $id : '/admin/users/profile')
            ->with('message', trans('locale.User profile') . ' ' . $user->first_name . ' ' . trans('locale.successfully updated'));
    }

    /**
     * Обновление дополнительной информации о профиле
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function adminUpdateInformationAction(Request $request, $id){
        $user = User::find($id);
        $user_data = $user->fullData();

        $user->socials = json_encode($request->socials, JSON_UNESCAPED_UNICODE);
        $user->user_birth = date('Y-m-d', strtotime($request->user_birth));

        $user->fill($request->only(['gender', 'language', 'phone', 'city', 'address', 'url', 'company']));
        $user->saveLocalization($request);
        $user->save();

        Action::updateEntity(User::find($id), $user_data);

        if($request->ajax()){
            return response()->json([
                'result' => 'success',
                'message' => 'Профиль пользователя ' . $user->first_name . ' успешно обновлен.'
            ]);
        }

        return redirect('/admin/users/edit/' . $user->id)
            ->with('message', trans('locale.User profile') . ' ' . $user->first_name . ' ' . trans('locale.successfully updated'));
    }

    public function adminUpdatePasswordAction(Request $request, $id){
        $user = User::find($id);
        $rules = [];

        $rules['password'] = 'required|confirmed|min:6';
        $rules['password_confirmation'] = 'required';

        $validator = Validator::make($request->all(), $rules, $this->messages);

        if($validator->fails()){
            if($request->ajax()){
                return response()->json([
                    'result' => 'error',
                    'errors' => $validator->messages()
                ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('message-error', trans('locale.Saving failed'))
                ->withErrors($validator);
        }

        Sentinel::update($user, [
            'email' => $user->email,
            'password' => $request->password
        ]);

        if($request->ajax()){
            return response()->json([
                'result' => 'success',
                'message' => str_replace(':name', $user->name, trans('locale.User password updated successfully'))
            ]);
        }

        return redirect('/admin/users/edit/'.$id)
            ->with('message', str_replace(':name', $user->name, trans('locale.User password updated successfully')));
    }

    /**
     * Смена аватарки
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminChangePhotoAction(Request $request, $id){
        $me = Sentinel::check();

        if($me->id == $id){
            $user = $me;
        }elseif($me->hasAccess('users.write')){
            $user = User::find($id);
        }

        if(empty($user)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Access denied')]);
        }

        if(!empty($user->photo) && is_file(public_path($user->photo)))
            unlink(public_path($user->photo));

        if($request->hasFile('photo')){
            $file = $request->file('photo');
            $mime = $file->getClientMimeType();

            if($mime == 'image/png'){
                $file_name = $id.'.png';
            }elseif($mime == 'image/jpeg'){
                $file_name = $id.'.jpg';
            }

            if(!empty($file_name))
                $file->move(public_path('uploads/users'), $file_name);
        }

        $user->photo = !empty($file_name) ? 'uploads/users/'.$file_name : null;
        $user->save();

        return response()->json(['result' => 'success']);
    }

    /**
     * Обновление SEO данных автора
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateSeoAction(Request $request, $id){
        $user = User::find($id);

        if(empty($user)){
            return response()->json(['result' => 'error', 'message' => trans('locale.User not found')], 200);
        }

        $seo_data = $user->seo ? $user->seo->fullData() : [];
        $user->saveSeo($request);
        $user->load('seo');
        $user->slug = Str::slug(str_replace(['/', '_'], '', $user->seo->url));

        Action::updateEntity($user->seo, $seo_data);

        return response()->json(['result' => 'success', 'message' => trans('locale.Changes saved')], 200);
    }

    public function adminProfileAction(){
        $me = Sentinel::check();

        return view('admin.users.profile')
            ->with('user', User::find($me->id))
            ->with('socials', $this->socials)
            ->with('languages', config('app.locales_names'))
            ->with('user_languages', config('app.languages_names'));
    }

    public function updatePassword(Request $request)
    {
        $user = Sentinel::check();

        if (!password_verify($request->old_password, $user->password)) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'old_password' => trans('locale.Wrong old password')
                ])
                ->with('process', 'update_password');
        }

        if ($user) {
            $user = User::find($user->id);
        }

        $rules = [
            'password' => 'required|min:4|confirmed',
            'password_confirmation' => 'required|min:4'
        ];

        $validator = Validator::make($request->all(), $rules, [
            'password.required' => trans('locale.This field is required'),
            'password.min' => trans('locale.Password must be at least 4 characters'),
            'password.confirmed' => trans('locale.Passwords do not match'),
            'password_confirmation.required' => trans('locale.Confirm password is required'),
            'password_confirmation.min' => trans('locale.Password must be at least 4 characters')
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator)
                ->with('process', 'update_password');
        }

        if ($request->password) {
            $user->password = password_hash($request->password, PASSWORD_DEFAULT);
        }

        $user->push();

        return redirect()
            ->back();
    }

    public function updateSubscr(Request $request)
    {
        $user = Sentinel::check();
        if ($user) {
            $user = User::find($user->id);
        }

        $rules = [
            'subscr' => 'required'
        ];

        $messages = [
            'subscr.required' => trans('locale.Subscription type not selected')
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json($validator);
        }

        if ($request->subscr) {
            $user->user_data->subscribe = $request->subscr;
        }

        $user->push();

        return response()->json(['success' => true]);
    }

    public function get_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function subscribe(Request $request, User $user)
    {
        $rules = [
            'email' => 'required|email'
        ];

        $messages = [
            'email.required' => trans('app.You_did_not_enter_an_email_address'),
            'email.email' => trans('app.Incorrect_email_address'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 200);
        }

		$user_exists = User::where('email', $request->email)->first();

		if($user_exists){
			$subscribe = $user->where('id', $user_exists->id)->first();
			$subscribe->user_data->subscribe = 1;
			$subscribe->save();
		}else{
			$user = Sentinel::registerAndActivate(array(
				'email'    => $request->email,
				'password' => 'null',
				'permissions' => null
			));

			$role = Sentinel::findRoleBySlug('unregistered');
			$role->users()->attach($user);
		}

        return response()->json(['success' => trans('app.You_have_successfully_subscribed_to_the_news')]);
    }

    public function statistic($id)
    {
        $orders = Order::where('user_id', $id)->get();

        return view('admin.users.orders')->with('orders', $orders)->with('user', User::find($id));
    }

    public function reviews($id)
    {
        $reviews = Review::where('user_id', $id)->paginate(10);

        return view('admin.users.reviews')->with('reviews', $reviews)->with('user', User::find($id));
    }

    public function siteReviews($id)
    {
        $shopreviews = SiteReview::where('user_id', $id)->paginate(10);

        return view('admin.users.shopreviews')->with('shopreviews', $shopreviews)->with('user', User::find($id));
    }

    public function sendMail()
    {
        $domain = $_SERVER['HTTP_HOST'];
        $_SESSION['http_host'] = $domain;
        $title = '';
        $subject = trans('locale.Callback request');

        $files = [];
        if (count($_FILES)) {
            foreach ($_FILES as $file) {
                if ($file["error"] == 0) {
                    $tmp_name = $file["tmp_name"];
                    // basename() can protect against file system attacks;
                    // additional validation/cleaning of the filename may be needed
                    $name = basename($file["name"]);
                    $storage = storage_path(trans('locale.app') . DIRECTORY_SEPARATOR . trans('locale.temp') . DIRECTORY_SEPARATOR . $name);
                    move_uploaded_file($tmp_name, $storage);
                    $files[] = array('path' => $storage, 'name' => $tmp_name);
                }
            }
        }

        if (array_key_exists('data', $_POST)) {
            $eol = PHP_EOL;

            $msg = "";

            $msg .= "<html><body style='font-family:Arial,sans-serif;'>";
            $msg .= "<h2 style='color:#161616;font-weight:bold;font-size:30px;border-bottom:2px dotted #bd0707;'>" . str_replace(':domain', $domain, trans('locale.New request on website')) . " " . $title . "</h2>" . $eol;

            $data = json_decode($_POST['data']);

            $session_data = [
                'sourse' => trans('locale.Search engine'),
                'term' => trans('locale.Keyword'),
                'campaign' => trans('locale.Campaign')
            ];

            foreach ($data as $key => $params) {
                if (!empty($params->title) && !empty($params->val)) {
                    $val = $this->prepare_data($params->val, $key);
                    $msg .= "<p><strong>$params->title:</strong> $val</p>" . $eol;
                    if (isset($session_data[$key]))
                        unset($session_data[$key]);
                }
            }

            foreach ($session_data as $key => $title) {
                if (array_key_exists($key, $_SESSION)) {
                    $val = $this->prepare_data($_SESSION[$key], $key);
                    $msg .= "<p><strong>$title:</strong> $val</p>" . $eol;
                }
            }

            $msg .= "</body></html>";

            $setting = new Setting();

            Mail::send('emails.sendmail', ['html' => $msg], function ($msg) use ($setting, $subject, $files) {
                $msg->from('admin@' . str_replace(['http://', 'https://'], '', env('APP_URL')), trans('locale.Online store') . ' G.Shop');
                $msg->to($setting->get_setting('notify_emails'));
                $msg->subject($subject);
                if (!empty($files)) {
                    foreach ($files as $file) {
                        $msg->attach($file['path'], ['as' => $file['name']]);
                    }
                }
            });

            header("HTTP/1.0 200 OK");
            echo '{"status":"success"}';

        } else {
            header("HTTP/1.0 404 Not Found");
            echo '{"status":"error"}';
        }

        if (!empty($files)) {
            foreach ($files as $file) {
                unlink($file['path']);
            }
        }
    }

    public function prepare_data($data, $key)
    {
        switch ($key) {
            case 'referer':
                return substr($data, 0, 30);
            case 'term':
                return urldecode($data);
            default:
                return $data;
        }
    }

    public function send_mail($to, $thm, $html, $path)
    {
        $fp = fopen($path, "r");
        if (!$fp) {
            print str_replace(':path', $path, trans('locale.File cannot be read'));
            exit();
        }

        $file = fread($fp, filesize($path));
        fclose($fp);

        $boundary = "--" . md5(uniqid(time())); // генерируем разделитель
        $headers = "MIME-Version: 1.0\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
        $multipart = "--$boundary\n";

        $kod = 'utf-8';
        $multipart .= "Content-Type: text/html; charset=$kod\n";
        $multipart .= "Content-Transfer-Encoding: Quot-Printed\n\n";
        $multipart .= "$html\n\n";

        $message_part = "--$boundary\n";
        $message_part .= "Content-Type: application/octet-stream\n";
        $message_part .= "Content-Transfer-Encoding: base64\n";
        $message_part .= "Content-Disposition: attachment; filename = \"" . $path . "\"\n\n";
        $message_part .= chunk_split(base64_encode($file)) . "\n";
        $multipart .= $message_part . "--$boundary--\n";

        if (mail($to, $thm, $multipart, $headers)) {
            return 1;
        }
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $users = [[
            trans('locale.Email'),
            trans('locale.Phone'),
            trans('locale.First name'),
            trans('locale.Last name'),
            trans('locale.Middle name'),
            trans('locale.Gender'),
            trans('locale.Birthday'),
            trans('locale.City')
        ]];
        foreach (User::select(['users.*'])->join('role_users', function ($join) {
            $join->on('users.id', '=', 'role_users.user_id')
                ->whereIn('role_users.role_id', [5, 6]);
        })->with(['user_data'])->get() as $user) {
            $phone = is_object($user->user_data) ? $user->user_data->phone : '';
            if (strlen($phone) == 10) {
                $phone = '+38' . $phone;
            } elseif (strlen($phone) == 12) {
                $phone = '+' . $phone;
            }

            $users[] = [
                'email' => $user->email,
                'phone' => ' ' . $phone,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'patronymic' => $user->patronymic,
                'gender' => is_object($user->user_data) && $user->user_data->gender ? trans('locale.Male') : trans('locale.Female'),
                'user_birth' => is_object($user->user_data) ? $user->user_data->user_birth : '',
                'city' => is_object($user->user_data) ? $user->user_data->city : ''
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getStyle('B1:B' . (count($users) + 1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->fromArray($users, NULL, 'A1');

        $streamedResponse = new StreamedResponse();
        $streamedResponse->setCallback(function () use ($spreadsheet) {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        });

        $streamedResponse->setStatusCode(200);
        $streamedResponse->headers->set('Content-Type', 'text/csv');
        $streamedResponse->headers->set('Content-Disposition', 'attachment; filename="' . trans('locale.Customers') . '.xls"');

        return $streamedResponse->send();
    }

    public function import(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->hasFile('import_file')) {
            $file = $request->file('import_file');
            $file_name = $file->getClientOriginalName();

            $file->move(storage_path('app/imports'), $file_name);

            $path = storage_path('app/imports/' . $file_name);

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $data = $spreadsheet->getSheet(0)->toArray();

            // Check if data is not empty before processing
            if (!empty($data)) {
                $headings = array_diff(array_shift($data), array(null));
                if (!empty($headings)) {
                    array_walk(
                        $data,
                        function (&$row) use ($headings) {
                            if (is_array($row)) {
                                $row = array_combine($headings, array_slice($row, 0, count($headings)));
                            }
                        }
                    );
                }
            }
        }

        return response()->json(['result' => 'success']);
    }

    /**
     * Получение пользовательских доступов
     *
     * @param $user
     * @return array
     */
    private function getUserModulesPermissions($user): array {
        $modules = [];
        $permissions = [];

        // Доступы роли
        foreach($user->roles as $role){
            if(empty($permissions)){
                $permissions = $role->permissions;
            }else{
                foreach($role->permissions as $permission => $available){
                    if($available){
                        $permissions[$permission] = true;
                    }
                }
            }
        }

        // Персональные доступы
        if(!empty($user->permissions)){
            foreach($user->permissions as $permission => $available){
                if($available){
                    $permissions[$permission] = true;
                }
            }
        }

        // Доступные разрешения модулей
        foreach($this->modules as $module => $params){
            $module_permissions = [];

            foreach($params['permissions'] as $permission){
                $module_permissions[$permission] = !empty($permissions[$module.'.'.$permission]);
            }

            $modules[$module] = (object)[
                'name' => $params['name'],
                'permissions' => (object)$module_permissions
            ];
        }

        return $modules;
    }
}
