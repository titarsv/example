<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Laravel\Facades\Reminder;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\UserData;
use App\Models\User;
use App\Models\File;

class AuthenticationController extends Controller
{
    /**
     * Страница авторизации
     *
     * @return mixed
     */
    public function loginAction(Request $request)
    {
        $redirect = Cookie::get('redirect');
        if (Sentinel::check()){
//            $user_id = Sentinel::check()->id;
//            $cart = CartController::cartToUser($user_id);
//            if(is_object($cart)){
//                $cart->full_cart_update();
//            }

            if(!empty($redirect)){
                return redirect('/'.$redirect);
            }elseif(isset($request->redirect)){
                return redirect('/'.$request->redirect);
            }elseif (Sentinel::inRole('admin') or Sentinel::inRole('manager') or Sentinel::inRole('moderator') or Sentinel::inRole('marketer')){
                return redirect('/admin/pages');
            } elseif (Sentinel::inRole('user')){
                return redirect('/user');
            }else{
                return redirect('/');
            }
        }else{
            if(isset($request->redirect)){
                Cookie::queue('redirect', $request->redirect, 60, null, null, false, false);
            }
            $pageConfigs = ['bodyCustomClass' => 'bg-full-screen-image'];
            return view('auth.login', ['pageConfigs' => $pageConfigs])
                ->with('process', 'authenticate');
        }
    }

    /**
     * Страница регистрации
     * @return mixed
     */
    public function registration(Request $request)
    {
        if (Sentinel::check()) {
            if(!empty($redirect)){
                return redirect('/'.$redirect);
            }elseif(isset($request->redirect)){
                return redirect('/'.$request->redirect);
            }elseif (Sentinel::inRole('admin') or Sentinel::inRole('manager')) {
                return redirect('/admin');
            } elseif (Sentinel::inRole('user')) {
                return redirect('/user');
            }
        } else {
            if(isset($request->redirect)){
                Cookie::queue('redirect', $request->redirect, 60, null, null, false, false);
            }
            return view('registration')->with('process', 'authenticate');
        }
    }

    /**
     * Процесс авторизации
     *
     * @param Request $request
     * @return mixed
     */
    public function authenticate(Request $request){
        $rules = [
            'email'     =>'required|email',
            'password'  => 'required',
        ];

        $messages = [
            'email.required'    => trans('validation.required', ['attribute' => 'email']),
            'email.email'       => trans('validation.email', ['attribute' => 'email']),
            'password.required' => trans('validation.required', ['attribute' => __('auth.password')]),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails()){
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator)
                ->with('process', 'authenticate');
        }

        $credentials = [
            'email'    => $request->get('email'),
            'password' => $request->get('password'),
        ];

        $user = Sentinel::findByCredentials($credentials);
        if(!$user){
            return redirect()
                ->back()
                ->withErrors(['email' => __('auth.email_not_registered')])
                ->withInput($request->except('password'))
                ->with('process', 'authenticate');
        }

        if(!empty($request->remember_me))
            $auth = Sentinel::authenticateAndRemember($credentials);
        else
            $auth = Sentinel::authenticate($credentials);

        if(!$auth){
            return redirect()
                ->back()
                ->withErrors(['password' => __('auth.wrong_password')])
                ->withInput($request->except('password'))
                ->with('process', 'authenticate');
        }
        if(isset($request->referrer) and $request->referrer == '/cart'){
            return redirect('/cart');
        }

        return redirect ('/login')
            ->with('process', 'authenticate');
    }

    public function register()
    {
        if (Sentinel::check()) {
            return redirect('/user');
        } else {
            return view('register');
        }
    }

    /**
     * Переадресация в соцсеть для авторизации
     *
     * @param null $provider
     * @return mixed
     */
    public function getSocialAuth($provider=null){
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Авторизация/регистрация через соцсеть
     *
     * @param null $provider
     * @return \Illuminate\Http\RedirectResponse|string
     */
    public function getSocialAuthCallback($provider=null){
        if($user = Socialite::driver($provider)->user()){
            $user_exists = User::where('email', $user->email)->first();

            if($user_exists){
                Sentinel::authenticateAndRemember(Sentinel::findById($user_exists->id));

                return redirect ('/login')
                    ->with('process', 'authenticate');
            }else{
                $name = explode(' ', $user->name);
                $file_id = null;
                if(!empty($user->avatar_original)){
                    $file = new File();
                    $file = $file->uploadFromUrlImages($user->avatar_original);
                    if(!empty($file))
                        $file_id = $file->id;
                }
                $credentials = [
                    'first_name' => isset($name[0]) ? $name[0] : '',
                    'last_name' => isset($name[1]) ? $name[1] : '',
                    'email'     => $user->email,
                    'password'  => Hash::make(str_random(8)),
                    'permissions' => [
                        'user' => true
                    ]
                ];

                $user = Sentinel::registerAndActivate($credentials);
                $userRole = Sentinel::findRoleBySlug('user');
                $userRole->users()->attach($user);

//                CartController::cartToUser($user->id);

                $user_data = new UserData();

                $user_data->create([
                    'user_id'   => $user->id,
                    'file_id'  => $file_id,
                    'phone'     => null,
                    'subscribe' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $auth = Sentinel::authenticateAndRemember($credentials);
                if($auth){
                    return redirect('/user')
                        ->with('status', __('auth.registration_success'));
                }
            }
        }

        abort(404);
    }

    /**
     * Процесс регистрации
     * @param Request $request
     * @param UserData $user_data
     * @return mixed
     */
    public function store(Request $request, UserData $user_data)
    {
        $rules = [
            'first_name' => 'required',
            'phone'     => 'required',
            'email'     =>'required|email',
            'password'  => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ];

        $messages = [
            'first_name.required' => 'Не заполнены обязательные поля!',
            'phone.required'    => 'Не заполнены обязательные поля!',
            'phone.regex'       => 'Некорректный телефон!',
            'email.required'    => 'Не заполнены обязательные поля!',
            'email.email'       => 'Некорректный email-адрес!',
            'password.required' => 'Не заполнены обязательные поля!',
            'password.min'      => 'Пароль должен быть не менее 6 символов!',
            'password.confirmed' => 'Введенные пароли не совпадают!'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator)
                ->with('process', 'registration');
        }

        $user_exists = User::where('email', $request->email)->first();

        if ($user_exists){
            $user = Sentinel::findById($user_exists->id);
            if($user->inRole('unregister_user')){

                $credentials = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'password' => $request->password,
                    'permissions' => [
                        'user' => true
                    ]
                ];

                Sentinel::update($user, $credentials);

                $role = Sentinel::findRoleBySlug('unregister_user');
                $role->users()->detach($user);

                $userRole = Sentinel::findRoleBySlug('user');
                $userRole->users()->attach($user);

                $user->user_data->subscribe = $request->subscribe;
                $user->user_data->phone = htmlspecialchars($request->phone);
                $user->push();

//                $data = $user_data->where('user_id', $user->id)->first();
//                $data->phone = $request->phone;
//                $data->save();

                $credentials['email'] = $request->email;
                $auth = Sentinel::authenticateAndRemember($credentials);
                $user_id = $user_exists->id;
                if($auth){
                    return redirect('/user')
                        ->with('status', 'Вы успешно зарегистрированы! Добро пожаловать в личный кабинет');
                } else {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['error' => 'При регистрации произошла ошибка!'])
                        ->with('process', 'registration');
                }

            } else {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['email' => 'Пользователь с таким email-ом уже зарегистрирован!'])
                    ->with('process', 'registration');
            }
        }

        $credentials = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email'     => $request->email,
            'password'  => $request->password
        ];

        $user = Sentinel::registerAndActivate($credentials);
        $userRole = Sentinel::findRoleByName('user');
        $userRole->users()->attach($user);

        $user_data->create([
            'user_id'   => $user->id,
            'image_id'  => 1,
            'phone'     => $request->phone,
            'subscribe' => $request->subscribe,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $auth = Sentinel::authenticateAndRemember($credentials);
        if($auth){
            return redirect('/user')
                ->with('status', 'Вы успешно зарегистрированы! Добро пожаловать в личный кабинет');
        }

    }

    public function forgotten()
    {
        return view('users.forgotten');
    }

    public function reminder(Request $request, Mail $mail)
    {
        $rules = [
            'email'     =>'required|email',
        ];

        $messages = [
            'email.required'    => 'Введите email-адрес!',
            'email.email'       => 'Некорректный email-адрес!',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator);
        }

        $user_exists = User::where('email', $request->email)->first();

        if ($user_exists) {
            $user = Sentinel::findById($user_exists->id);

            ($reminder = Reminder::exists($user)) || ($reminder = Reminder::create($user));

            Mail::send('emails.reminder', ['user' => $user, 'reminder' => $reminder], function($msg) use ($user, $request){
                $msg->from('admin@kam-teh.com.ua', 'kam-teh.com.ua');
                $msg->to($request->email);
                $msg->subject('Восстановление пароля');
            });

            return view('users.forgotten_email_sent');
        } else {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['email' => 'Пользователь с таким email-адресом не зарегистрирован!']);
        }

    }

    public function lostpassword(Request $request)
    {
        if($request->id && $request->code){
            $user = Sentinel::findById($request->id);

            if (Reminder::exists($user, $request->code)) {
                return view('users.lostpassword');
            }
        } else {
            return redirect('/');
        }
    }

    public function changePassword(Request $request)
    {
        $rules = [
            'password'  => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
        ];

        $messages = [
            'password.required' => 'Введите новый пароль!',
            'password.min'      => 'Пароль должен быть не менее 6 символов!',
            'password.confirmed' => 'Введенные пароли не совпадают!',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        $user = Sentinel::findById($request->id);
        Reminder::complete($user, $request->code, $request->password);

        return view('users.lostpassword_complete');
    }

    /**
     * Сохранение пользователей в БД как незарегистрированных
     *
     * @param Request $request
     * @return int
     */
    public function storeAsUnregistered(Request $request)
    {
        $credentials = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone'     => $request->phone,
            'email'     => $request->email,
            'password'  => 'null',
            'permissions' => [
                'unregistered' => true
            ]
        ];

        $user = Sentinel::registerAndActivate($credentials);

        $userRole = Sentinel::findRoleBySlug('unregister_user');
        $userRole->users()->attach($user);

        if ($user->id) {
            $response = $user;
        } else {
            $response = 0;
        }

        return $response;
    }

    /**
     * Выход
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logoutAction(){
        Sentinel::logout();
        return redirect('/');
    }
}
