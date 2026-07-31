<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\Newpost;
use App\Models\Setting;
use App\Models\Justin;
use App\Models\Action;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use App;

class OrdersController extends Controller
{
    /**
     * Страница благодарности
     *
     * @param Request $request
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function thanksAction(Request $request){
        $order = Order::find($request->order_id);
        if($order->viewed)
            return redirect('/');

        if($order->status_id >= 2 || ($order->payment != 'mycryptocheckout' && $order->status_id >= 1))
            $order->update(['viewed' => 1]);

        $delivery = $order->getDeliveryInfo();

        return view('public.thanks')
            ->with('order', $order)
            ->with('user_info', $order->getUserInfo())
            ->with('products', Product::orderBy('popularity', 'desc')->where('visible', 1)->limit(7)->get())
            ->with('delivery_cost', isset($delivery['delivery_cost']) ? $delivery['delivery_cost'] : 0);
    }

    /**
     * Список заказов
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function adminIndexAction(){
        return view('admin.orders.index', [
            'order_status' => OrderStatus::all(),
            'localization' => json_encode(['datatable' => trans('datatable')])
        ]);
    }

    /**
     * Фильтр заказов
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminListAction(Request $request){
        $user = Sentinel::getUser();
        if(!is_null($user)){
            $user = User::find($user->id);
        }

        $query = Order::select('orders.*');

        if($request->has('search.value')){
            $text = trim($request->search['value']);
            if(!empty($text))
                $query->where('id', $text);
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

        $orders = $query->get();

        $data = [];
        foreach($orders as $order) {
            $order->user = json_decode($order->user_info, true);
            if ($order->status_id) {
                if ($order->status_id == 1) {
                    $order->class = 'warning';
                } elseif ($order->status_id == 6) {
                    $order->class = 'danger';
                } elseif ($order->status_id == 7) {
                    $order->class = 'warning';
                } else {
                    $order->class = 'info';
                }
            } else {
                $order->class = 'danger';
            }

            $actions = [];
            if ($user->hasAccess(['categories.write'])) {
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/orders/edit/' . $order->id)
                ];
            }
            if ($user->hasAccess(['categories.delete'])) {
                $actions[] = [
                    'type' => 'delete',
                    'id' => $order->id,
                    'name' => trans('locale.order.order_number') . $order->id
                ];
            }

            $products = [];
//            foreach($order->products()->with(['image', 'localization'])->get() as $product) {
            foreach($order->getProducts() as $product){
                $products[] = [
                    'image' => !empty($product['product']) && !empty($product['product']->image) ? $product['product']->image->url([60, 60]) : '/images/larchik/no_image.jpg',
                    'name' => !empty($product['product']) ? $product['product']->name : 'Deleted',
                    'link' => !empty($product['product']) ? $product['product']->link() : null
                ];
            }

            $delivery_info = $order->getDeliveryInfo();
            $tracking = '';
            if(!empty($delivery_info['tracking'])){
                foreach ($delivery_info['tracking'] as $track){
                    if(!empty($tracking)){
                        $tracking .= '<br>';
                    }
                    $tracking .= '<a href = "https://www.royalmail.com/track-your-item/?trackNumber='.$track['tracking_number'].'" target = "_blank">'.$track['tracking_number'].'</a>';
                }
            }

            $payment_status = [
                'status' => $order->payment_status ? 'Paid' : 'Waiting for payment',
                'class' => $order->payment_status ? 'success' : 'primary'
            ];

            $data[] = [
                'id' => $order->id,
                'products' => $products,
                'status' => ['id' => $order->status_id, 'status' => $order->status ? $order->status->status : trans('locale.Unfinished'), 'class' => $order->class],
                'name' => isset($order->user['name']) ? $order->user['name'] : '',
                'tracking' => $tracking,
                'payment_status' => $payment_status,
                'price' => sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), round($order->full_price, 2)),
                'date' => $order->created_at->timezone('Europe/Kiev')->format('Y-m-d H:i:s'),
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Order::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    public function create(){
        return view('admin.orders.create')->with([
            'orders_statuses' => OrderStatus::all(),
        ]);
    }

    public function store(Request $request){
        $rules = [
            'user_name'    => 'required',
            'user_phone'   => 'required|regex:/^[0-9\-! ,\'\"\/+@\.:\(\)]+$/',
            'user_email'   => 'required|email'
        ];
        $messages = [
            'user_name.required'  => trans('locale.order.validation.name_required'),
            'user_phone.required' => trans('locale.order.validation.phone_required'),
            'user_phone.regex'    => trans('locale.order.validation.phone_regex'),
            'user_email.required' => trans('locale.order.validation.email_required'),
            'user_email.email'    => trans('locale.order.validation.email_email'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()){
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator);
        }

        $products = [];
        $total_quantity = 0;
        $total_price = 0;
        $history = [];

        if(!empty($request->products)) {
            foreach ($request->products as $product) {
                if (isset($products[$product['code']])) {
                    if ($product['qty']) {
                        $products[$product['code']]['quantity'] = $product['qty'];
                        $total_quantity += $product['qty'];
                        $total_price += $products[$product['code']]['price'] * $product['qty'];
                    } else {
                        unset($products[$product['code']]);
                    }
                } elseif ($product['qty'] > 0) {
                    $prod = Product::find($product['code']);
                    if (!empty($prod)) {
                        $products[$product['code']] = [
                            'quantity' => $product['qty'],
                            'price' => $prod->price,
                            'sale' => 0,
                            'sale_percent' => 0
                        ];
                        $total_quantity += $product['qty'];
                        $total_price += $prod->price * $product['qty'];
                    }
                }
            }
        }

        $user_info = [];
        $user_info['name'] = $request->user_name;
        $user_info['phone'] = $request->user_phone;
        $user_info['email'] = $request->user_email;
        $user_info['comment'] = $request->comment;

        $delivery_info = [];
        $delivery_info['method'] = $request->delivery;
        if($delivery_info['method'] == 'pickup'){
            $delivery_info['info'] = [];
        }elseif($delivery_info['method'] == 'newpost' || $delivery_info['method'] == 'justin'){
            $delivery_info['info'] = [
                'region' => $request->region,
                'city' => $request->city,
                'warehouse' => $request->warehouse
            ];
        }elseif($delivery_info['method'] == 'courier'){
            $delivery_info['info'] = [
                'street' => $request->street,
                'house' => $request->house,
                'apartment' => $request->apartment
            ];
        }elseif($delivery_info['method'] == 'other'){
            $delivery_info['info'] = [
                'name' => $request->name,
                'region' => $request->region,
                'city' => $request->city,
                'warehouse' => $request->warehouse
            ];
        }

        $users = new User();
        $existed_user = $users->checkIfUnregistered($request->user_phone, $request->user_email);
        if(!is_null($existed_user)) {
            $user = $existed_user;
        } else {
            $register = new LoginController();
            $r = new Request();
            $name = explode(' ', $request->user_name);
            $r->merge([
                'first_name' => $name[0],
                'last_name' => isset($name[1]) ? $name[1] : '',
                'phone'     => $request->user_phone,
                'email'     => $request->user_email,
            ]);
            $user = $register->storeAsUnregistered($r);
        }

        $id = Order::insertGetId([
            'user_id'   => $user->id,
            'products' => json_encode($products, JSON_UNESCAPED_UNICODE),
            'total_quantity' => $total_quantity,
            'total_price' => $total_price,
            'status_id' => $request->status,
            'user_info' => json_encode($user_info, JSON_UNESCAPED_UNICODE),
            'delivery' => json_encode($delivery_info, JSON_UNESCAPED_UNICODE),
            'notes' => $request->notes,
            'payment' => $request->payment,
            'history' => json_encode($history, JSON_UNESCAPED_UNICODE)
        ]);

        return redirect('/admin/orders/edit/'. $id)
            ->with('message-success', trans('locale.order.success.created', ['id' => $id]));
    }

    public function edit($id)
    {
        $order = Order::find($id);

        $order->user = json_decode($order->user_info);
        $order->date = $order->updated_at->format('d.m.Y');
        $order->time = $order->updated_at->format('H:i');
        if ($order->status_id) {
            if ($order->status_id == 1){
                $order->class = 'warning';
            } else {
                $order->class = 'info';
            }
        } else {
            $order->class = 'danger';
        }

        $settings = new Setting();

        $payment_methods = [];
        foreach($settings->get_setting('payment_methods') as $payment_method){
            $payment_methods[] = (object)['value' => $payment_method, 'name' => $settings->get_setting('payment_'.$payment_method.'_name_'.app()->getLocale())];
        }

        $currencies = [];
        $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
        $mcc_account_data = $mcc->account()->data;
        $curr = $mcc->currencies();
        $wallets = $mcc->wallets();
        foreach($wallets as $wallet){
            if(!isset($currencies[$wallet->currency_id])){
                $currency = $curr->get($wallet->currency_id);
                $currencies[$wallet->currency_id] = (object)['value' => $wallet->currency_id, 'name' => $currency->data->name . ' (' . $wallet->currency_id .  ')'];
            }
        }

        $orders_statuses = [
            (object)[
                'value' => 0,
                'name' => trans('locale.Unfinished')
            ]
        ];
        foreach(OrderStatus::all() as $order_status){
            $orders_statuses[] = (object)[
                'value' => $order_status->id,
                'name' => $order_status->status
            ];
        }

        return view('admin.orders.edit', [
            'order' => $order,
            'products' => $order->products()->get(),
            'next' => Order::where('id', '>', $order->id)->orderBy('id', 'asc')->first(),
            'prev' => Order::where('id', '<', $order->id)->orderBy('id', 'desc')->first(),
            'orders_statuses' => $orders_statuses,
            'delivery_info' => $this->prepareDeliveryInformation($order),
            'payment_methods' => $payment_methods,
            'currencies' => $currencies,
            'sms' => [
                'payment' => $settings->get_setting('sms_payment'),
                'delivery' => $settings->get_setting('sms_delivery'),
                'promo' => $settings->get_setting('sms_promo')
            ]
        ]);
    }

    /**
     * Подготовка к выводу информации о доставке
     *
     * @param $order
     * @return mixed
     */
    private function prepareDeliveryInformation($order){
        $delivery_info = $order->getDeliveryInfo();
        if(isset($delivery_info['method']) && $delivery_info['method'] == trans('locale.order.delivery_methods.newpost_warehouse')){
            $original_delivery_info = json_decode($order->delivery, true);
            $newpost = new Newpost();
            $delivery_info['region'] = [
                'options' => $newpost->getRegions(),
                'selected' =>  !empty($original_delivery_info['info']['region']) ? $original_delivery_info['info']['region'] : ''
            ];

            if(!empty($original_delivery_info['info']['region'])){
                $delivery_info['city'] = [
                    'options' => $newpost->getCities($newpost->getRegionRef($original_delivery_info['info']['region'])->region_id),
                    'selected' => !empty($original_delivery_info['info']['city']) ? $original_delivery_info['info']['city'] : ''
                ];
            }else{
                $delivery_info['city'] = [
                    'options' => [(object)['id' => null, 'name_ru' => trans('locale.Select region')]],
                    'selected' => !empty($original_delivery_info['info']['city']) ? $original_delivery_info['info']['city'] : ''
                ];
            }

            if(!empty($original_delivery_info['info']['city'])){
                $delivery_info['warehouse'] = [
                    'options' => $newpost->getWarehouses($newpost->getCityRef($original_delivery_info['info']['city'])->city_id),
                    'selected' => !empty($original_delivery_info['info']['warehouse']) ? $original_delivery_info['info']['warehouse'] : ''
                ];
            }else{
                $delivery_info['warehouse'] = [
                    'options' => [(object)['id' => null, 'address_ru' => trans('locale.order.select_settlement')]],
                    'selected' => !empty($original_delivery_info['info']['warehouse']) ? $original_delivery_info['info']['warehouse'] : ''
                ];
            }
        }elseif(isset($delivery_info['method']) && $delivery_info['method'] == trans('locale.order.delivery_methods.newpost_courier')){
            $original_delivery_info = json_decode($order->delivery, true);
            $newpost = new Newpost();
            $delivery_info['region'] = [
                'options' => $newpost->getRegions(),
                'selected' =>  !empty($original_delivery_info['info']['region']) ? $original_delivery_info['info']['region'] : ''
            ];

            if(!empty($original_delivery_info['info']['region'])){
                $delivery_info['city'] = [
                    'options' => $newpost->getCities($newpost->getRegionRef($original_delivery_info['info']['region'])->region_id),
                    'selected' => !empty($original_delivery_info['info']['city']) ? $original_delivery_info['info']['city'] : ''
                ];
            }else{
                $delivery_info['city'] = [
                    'options' => [(object)['id' => null, 'name_ru' => trans('locale.order.select_region')]],
                    'selected' => !empty($original_delivery_info['info']['city']) ? $original_delivery_info['info']['city'] : ''
                ];
            }
        }elseif(isset($delivery_info['method']) && $delivery_info['method'] == trans('locale.order.delivery_methods.justin_pickup')){
            $original_delivery_info = json_decode($order->delivery, true);
            $justin = new Justin();
            $delivery_info['region'] = [
                'options' => $justin->getRegions(),
                'selected' =>  !empty($original_delivery_info['info']['region']) ? $original_delivery_info['info']['region'] : ''
            ];
            foreach($delivery_info['region']['options'] as $id => $option){
                $option['id'] = $id;
                $option['name_ru'] = $option['name'];
                $delivery_info['region']['options'][$id] = (object)$option;
            }

            if(!empty($original_delivery_info['info']['region'])){
                $delivery_info['city'] = [
                    'options' => $justin->getCities($original_delivery_info['info']['region']),
                    'selected' => !empty($original_delivery_info['info']['city']) ? $original_delivery_info['info']['city'] : ''
                ];
                foreach($delivery_info['city']['options'] as $id => $option){
                    $option['id'] = $id;
                    $option['name_ru'] = $option['name'];
                    $delivery_info['city']['options'][$id] = (object)$option;
                }
            }else{
                $delivery_info['city'] = [
                    'options' => [(object)['id' => null, 'name_ru' => trans('locale.order.select_region')]],
                    'selected' => !empty($original_delivery_info['info']['city']) ? $original_delivery_info['info']['city'] : ''
                ];
            }

            if(!empty($original_delivery_info['info']['city'])){
                $delivery_info['warehouse'] = [
                    'options' => $justin->getWarehouses($original_delivery_info['info']['city']),
                    'selected' => !empty($original_delivery_info['info']['warehouse']) ? $original_delivery_info['info']['warehouse'] : ''
                ];
                foreach($delivery_info['warehouse']['options'] as $id => $option){
                    $option['id'] = $id;
                    $option['address_ru'] = $option['name'];
                    $delivery_info['warehouse']['options'][$id] = (object)$option;
                }
            }else{
                $delivery_info['warehouse'] = [
                    'options' => [(object)['id' => null, 'address_ru' => trans('locale.order.select_settlement')]],
                    'selected' => !empty($original_delivery_info['info']['warehouse']) ? $original_delivery_info['info']['warehouse'] : ''
                ];
            }
        }

        return $delivery_info;
    }

    public function adminUpdateAction($id, Request $request)
    {
        $order = Order::find($id);
        $history = json_decode($order->history, true);

        $user_info = json_decode($order->user_info, true);
        $user_info['name'] = $request->name;
        $user_info['email'] = $request->email;
        $user_info['comment'] = $request->comment;

        $delivery_info = json_decode($order->delivery, true);

        $delivery_info['info']['city'] = $request->city;
        $delivery_info['info']['street'] = $request->street;
        $delivery_info['info']['post_code'] = $request->post_code;

        if($request->status_id !== $order->status_id){
            $status = OrderStatus::find($request->status_id);
            $history[time()] = [
                'date' => now()->toDateTimeString(),
                'status' => trans('locale.order.status_changed', ['status' => $status->status]),
                'comment' => 'Previous status: ' . ($order->status ? $order->status->status : trans('locale.Unfinished')) . '. New status: '.OrderStatus::find($request->status_id)->status
            ];
        }

        $order->update([
            'status_id' => $request->status_id,
            'user_info' => json_encode($user_info, JSON_UNESCAPED_UNICODE),
            'delivery' => json_encode($delivery_info, JSON_UNESCAPED_UNICODE),
            'payment' => $request->payment,
            'payment_status' => $request->payment_status,
            'mcc_crypto_currency' => !empty($request->mcc_crypto_currency) ? $request->mcc_crypto_currency : null,
            'comment' => $request->comment,
            'history' => json_encode($history, JSON_UNESCAPED_UNICODE)
        ]);

        return response()->json(['result' => 'success', 'message' => trans('locale.order.success.updated', ['id' => $id])]);
    }

    public function adminUpdateProductsAction($id, Request $request){
        $order = Order::find($id);
        $products = json_decode($order->products, true);
        $total_quantity = 0;
        $total_price = 0;
        $history = $order->history;

        if(!empty($request->products)) {
            foreach ($request->products as $product) {
                if (isset($products[$product['code']])) {
                    if ($product['qty']) {
                        $products[$product['code']]['quantity'] = $product['qty'];
                        $total_quantity += $product['qty'];
                        $total_price += $products[$product['code']]['price'] * $product['qty'];
                    } else {
                        unset($products[$product['code']]);
                    }
                } elseif ($product['qty'] > 0) {
                    $prod = Product::find($product['code']);
                    if (!empty($prod)) {
                        $products[$product['code']] = [
                            'quantity' => $product['qty'],
                            'price' => $prod->price,
                            'sale' => 0,
                            'sale_percent' => 0
                        ];
                        $total_quantity += $product['qty'];
                        $total_price += $prod->price * $product['qty'];
                    }
                }
            }
        }

        $order->update([
            'products' => json_encode($products, JSON_UNESCAPED_UNICODE),
            'total_quantity' => $total_quantity,
            'total_price' => $total_price,
            'payment' => $request->payment,
            'history' => json_encode($history, JSON_UNESCAPED_UNICODE)
        ]);
    }

    public function newOrderUser(Request $request)
    {
        $user_id = Sentinel::check()->id;
        $rules = [
            'first_name'            => 'required',
            'phone'                 => 'required|regex:/^[0-9\-! ,\'\"\/+@\.:\(\)]+$/i',
            'email'                 => 'required|email'
        ];
        $messages = [
            'first_name.required'    => trans('validation.required', ['attribute' => trans('validation.attributes.first_name')]),
            'phone.required'         => trans('validation.required', ['attribute' => trans('validation.attributes.phone')]),
            'phone.regex'            => trans('validation.phone', ['attribute' => trans('validation.attributes.phone')]),
            'email.required'         => trans('validation.required', ['attribute' => trans('validation.attributes.email')]),
            'email.email'            => trans('validation.email', ['attribute' => trans('validation.attributes.email')]),
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator);
        }
        User::find($user_id)->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name
        ]);

        $address = json_encode([
            'city' => $request->city,
            'street' => $request->street,
            'house' => $request->house,
            'flat' => $request->flat
        ], JSON_UNESCAPED_UNICODE);
        UserData::where('user_id', $user_id)->update([
            'phone'     => $request->phone,
            'adress' => $address,
            'other_data' => json_encode($request->except(['_token', 'first_name', 'last_name', 'city', 'street', 'house', 'flat']), JSON_UNESCAPED_UNICODE),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        $order_data = json_encode($request->except(['_token', 'first_name', 'last_name', 'email', 'phone', 'password', 'password_confirmation']), JSON_UNESCAPED_UNICODE);

        $this->orderStore($user_id, $order_data);
        return redirect('/user/history')
            ->with('status', trans('locale.order.order_created'));
    }

    public function newOrder(Request $request)
    {
//        return dd($request);
        $password = $request->password ? $request->password : 'null';
        $order_data = json_encode($request->except(['_token', 'first_name', 'last_name', 'email', 'phone', 'password', 'password_confirmation']), JSON_UNESCAPED_UNICODE);
        $rules = [
            'first_name'            => 'required',
            'phone'                 => 'required|regex:/^[0-9\-! ,\'\"\/+@\.:\(\)]+$/i',
            'email'                 => 'required|email'
        ];
        $messages = [
            'first_name.required'    => trans('validation.required', ['attribute' => trans('validation.attributes.first_name')]),
            'phone.required'         => trans('validation.required', ['attribute' => trans('validation.attributes.phone')]),
            'phone.regex'            => trans('validation.phone', ['attribute' => trans('validation.attributes.phone')]),
            'email.required'         => trans('validation.required', ['attribute' => trans('validation.attributes.email')]),
            'email.email'            => trans('validation.email', ['attribute' => trans('validation.attributes.email')]),
        ];

        $user_exists = User::where('email', $request->email)->first();

        if($request->registration == 'on'){
            $rules['email'] = 'required|email|unique:users';
            if ($user_exists){
                $user = Sentinel::findById($user_exists->id);
                if($user->inRole('unregistered')){
                    $rules['email'] = 'required|email';
                }
            }

            $rules['password'] = 'required|string|min:6|confirmed';
            $rules['password_confirmation'] = 'required|string|min:6';

            $messages['password.required'] = trans('validation.required', ['attribute' => trans('validation.attributes.password')]);
            $messages['password.min'] = trans('validation.min.string', [
                'min' => 6,
                'attribute' => trans('validation.attributes.password')
            ]);
            $messages['password.confirmed'] = trans('validation.confirmed', [
                'attribute' => trans('validation.attributes.password')
            ]);
            $messages['email.unique'] = trans('validation.unique', [
                'attribute' => trans('validation.attributes.email')
            ]);
        }else{
            //$request->merge(['password' => $password]);
        }
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($validator);
        }

        if ($user_exists){
            $user = Sentinel::findById($user_exists->id);
            $user_id = $user->id;

            if($user->inRole('unregistered')){
                if($request->registration == 'off'){
                    $id = $this->orderStore($user_id, $order_data);        // записываем в базу и радуемся
                    return response()->json(['result' => 'success', 'message' => trans('locale.order.success.order_created'), 'order_id' => $id]);
                }
                $credentials = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'password' => $request->password,
                    'permissions' => [
                        'user' => true
                    ]
                ];

                Sentinel::update($user, $credentials);

                $role = Sentinel::findRoleBySlug('unregistered');
                $role->users()->detach($user);

                $userRole = Sentinel::findRoleBySlug('user');
                $userRole->users()->attach($user);


                $id = $this->orderStore($user_id, $order_data);        // записываем в базу заказ и радуемся

                $data = UserData::where('user_id', $user_id)->first();
                $data->phone = $request->phone;
                $data->save();

                $credentials['email'] = $request->email;
                $auth = Sentinel::authenticateAndRemember($credentials);

                if($auth){
                    return redirect(App::getLocale() == 'ua' ? '/ua/user/history' : '/user/history')
                        ->with('status', trans('locale.order.success.order_created'));
                }else{
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['error' => trans('auth.registration_failed')]);
                }

            }else{
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['email' => trans('validation.unique', ['attribute' => 'email'])]);
            }
        }

        //return dd($request);
        if($request->registration == 'off'){
            $user_role = Sentinel::findRoleBySlug('unregistered');
            $credentials['permissions'] = ['unregistered' => true];
        }else{
            $user_role = Sentinel::findRoleBySlug('user');
            $credentials['permissions'] = ['user' => true];
        }
        $credentials = [
            'email' => $request->email,
            'password' => $password,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'permissions' => [
                'user' => true
            ]
        ];

        $new_user = Sentinel::registerAndActivate($credentials);

        $user_role->users()->attach($new_user);
        $user_id = $new_user->id;

        $id = $this->orderStore($user_id, $order_data);        // записываем в базу и радуемся заказу

        UserData::create([
            'user_id'   => $user_id,
            'image_id'  => 1,
            'phone'     => $request->phone,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        if($request->registration == 'on') {
            $auth = Sentinel::authenticateAndRemember($credentials);
            if ($auth) {
                return redirect('/user/history')
                    ->with('status', trans('locale.order.success.order_created'));
            }
        }else{
            return response()->json(['result' => 'success', 'message' => trans('locale.order.success.order_created'), 'order_id' => $id]);
        }
    }

    public function get_product_data(Request $request){
        $order = Order::find($request->order);
        $products = json_decode($order->products, true);

        if(isset($products[$request->product]))
            return response()->json(['result' => 'success', 'product' => $products[$request->product]]);
        else
            return response()->json(['result' => 'error', 'error' => trans('locale.order.error.product_not_found')]);
    }

    public function update_product_data(Request $request){
        $order = Order::find($request->order);
        $products = json_decode($order->products, true);

        if(isset($products[$request->product])) {
            if(isset($request->price)){
                $products[$request->product]['price'] = $request->price;
            }
            if(isset($request->sale)){
                $products[$request->product]['sale'] = $request->sale;
                $products[$request->product]['sale_percent'] = 0;
            }
            if(isset($request->sale_percent)){
                $products[$request->product]['sale_percent'] = $request->sale_percent;
                $products[$request->product]['sale'] = 0;
            }

            $total_quantity = 0;
            $total_price = 0;
            foreach($products as $key => $product){
                $total_quantity += $product['quantity'];
                $total_price += $product['price']*$product['quantity'] * (100 - $product['sale_percent']) / 100;
            }

            $order->update([
                'products' => json_encode($products, JSON_UNESCAPED_UNICODE),
                'total_quantity' => $total_quantity,
                'total_price' => round($total_price, 2)
            ]);
            return response()->json(['result' => 'success', 'html' => $this->getOrderForm($order)]);
        }else
            return response()->json(['result' => 'error', 'error' => trans('locale.order.error.product_not_found')]);
    }

    public function paymentAction(Request $request){
        $ipay = new Ipay('', '', true);
        if($ipay->updatePayment($request->xml) === false)
            abort(404);

        return '';
    }

    public function delivery(Request $request){
        $newpost = new Newpost();
        if(!empty($request->id)){
            $current_order_id = $request->id;
            $order = Order::find($current_order_id);
            $delivery = $order->getDeliveryInfo();
        }

        if(!empty($delivery) && !empty($delivery['city']))
            $city = $delivery['city'];

        if($request->delivery == 'newpost') {
            $regions = $newpost->getRegions();
            $region_id = null;
            $cities = null;
            $city_id = null;
            $warehouses = null;

            if(!empty($city)){
                $city = $newpost->findCity($city);

                if(!empty($city)){
                    $region_id = $city->region_id;
                    $cities = $newpost->getCities($region_id);
                    $city_id = $city->city_id;
                    $warehouses = $newpost->getWarehouses($city_id);
                }
            }

            return response()->json([
                'delivery' => view('admin.orders.delivery.newpost', [
                    'regions' => $regions,
                    'region_id' => $region_id,
                    'cities' => $cities,
                    'city_id' => $city_id,
                    'warehouses' => $warehouses,
                    'current_order_id' => isset($current_order_id) ? $current_order_id : '',
                    'lang' => App::getLocale()
                ])->render()
            ]);
        }elseif($request->delivery == 'justin'){
            $justin = new Justin();
            $regions = $justin->getRegions();
            $region_id = null;
            $city_id = null;
            $cities = null;
            $warehouses = null;

            return response()->json([
                'delivery' => view('admin.orders.delivery.justin', [
                    'regions' => $regions,
                    'region_id' => $region_id,
                    'cities' => $cities,
                    'city_id' => $city_id,
                    'warehouses' => $warehouses,
                    'current_order_id' => isset($current_order_id) ? $current_order_id : '',
                    'subtotal' => 0,
                    'total' => 0,
                    'lang' => App::getLocale()
                ])->render()
            ]);
        }else{
            $region_name = '';

            if(!empty($city)){
                $city = $newpost->findCity($city);

                if(!empty($city)){
                    $region = DB::table('newpost_regions')->where('region_id', $city->region_id)->first();

                    if(!empty($region)){
                        $region_name = $region->{'name_'.App::getLocale()};
                    }
                }
            }

            return response()->json([
                'delivery' => view('admin.orders.delivery.' . $request->delivery, [
                    'current_order_id' => isset($current_order_id) ? $current_order_id : '',
                    'region' => $region_name
                ])->render()
            ]);
        }
    }

    /**
     * Update order tracking information
     *
     * @param Request $request
     * @param int $id Order ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTracking(Request $request, $id) {
        $order = Order::findOrFail($id);
        $delivery = json_decode($order->delivery, true) ?: [];

        $request->validate([
            'tracking_number' => 'required|string|max:255',
            'mark_shipped' => 'sometimes',
            'action' => 'required|in:add_tracking,mark_shipped,delete_tracking'
        ]);

        if ($request->action === 'add_tracking') {
            $rules['shipped_date'] = 'required|date';
        }

        try {
            if($request->action == 'add_tracking'){
                // Update delivery info with tracking information
                if(!isset($delivery['tracking'])){
                    $delivery['tracking'] = [];
                }
                $delivery['tracking'][] = [
                    'tracking_number' => $request->tracking_number,
                    'shipped_date' => $request->shipped_date,
                    'mark_shipped' => $request->mark_shipped == 'true'
                ];

                $order->delivery = json_encode($delivery);

                $order->save();

                // Add to order history
                $history = json_decode($order->history, true) ?: [];
                $history[] = [
                    'date' => now()->toDateTimeString(),
                    'status' => 'Tracking information updated',
                    'comment' => 'Tracking number: ' . $request->tracking_number .
                        ' | Shipped date: ' . $request->shipped_date .
                        ($request->mark_shipped ? ' | Order marked as shipped' : '')
                ];
                $order->history = json_encode($history);
                $order->save();
            } elseif ($request->action == 'mark_shipped') {
                $trackingNumber = $request->tracking_number;
                $trackingFound = false;

                // Find and update the specific tracking number
                foreach ($delivery['tracking'] as &$tracking) {
                    if ($tracking['tracking_number'] === $trackingNumber) {
                        $tracking['mark_shipped'] = true;
                        $trackingFound = true;
                        break;
                    }
                }

                if ($trackingFound) {
                    $order->delivery = json_encode($delivery);

                    // Add to order history
                    $history = json_decode($order->history, true) ?: [];
                    $history[] = [
                        'date' => now()->toDateTimeString(),
                        'status' => 'Tracking marked as shipped',
                        'comment' => 'Tracking number: ' . $trackingNumber . ' marked as shipped'
                    ];
                    $order->history = json_encode($history);

                    $order->save();

                    return response()->json([
                        'success' => true,
                        'message' => trans('locale.Tracking marked as shipped successfully')
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => trans('locale.Tracking number not found')
                ], 404);
            } elseif ($request->action == 'delete_tracking') {
                $trackingNumber = $request->tracking_number;
                $trackingFound = false;

                if (isset($delivery['tracking'])) {
                    foreach ($delivery['tracking'] as $key => $tracking) {
                        if ($tracking['tracking_number'] === $trackingNumber) {
                            unset($delivery['tracking'][$key]);
                            $delivery['tracking'] = array_values($delivery['tracking']); // Re-index array
                            $trackingFound = true;
                            break;
                        }
                    }
                }

                if ($trackingFound) {
                    $order->delivery = json_encode($delivery);

                    // Add to order history
                    $history = json_decode($order->history, true) ?: [];
                    $history[] = [
                        'date' => now()->toDateTimeString(),
                        'status' => 'Tracking information deleted',
                        'comment' => 'Deleted tracking number: ' . $trackingNumber
                    ];
                    $order->history = json_encode($history);

                    $order->save();

                    return response()->json([
                        'success' => true,
                        'message' => trans('locale.Tracking information deleted successfully')
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => trans('locale.Tracking number not found')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => trans('locale.Tracking information updated successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('locale.Failed to update tracking information') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmOrderPaymentAction(Request $request){
        foreach(Order::where('created_at', '>', date('Y-m-d H:i:s', time() - 172800))->get() as $order){
            if($request->token == md5('crypto_payment_token_'.$order->id) && $order->status_id == 1){
                $order->update(['status_id' => 2]);
                break;
            }
        }

        return response()->json(['result' => 'success']);
    }

    public function trackOrderAction(Request $request){
        // Validate the request
        $validated = $request->validate([
            'order_id' => 'required|string|max:100',
            'email' => 'required|email|max:255',
        ]);

        $order = Order::where('id', $validated['order_id'])->first();

        if (!$order || $order->email !== $validated['email']) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, the order could not be found. Please contact us if you are having difficulty finding your order details.'
            ]);
        }

        return response()->json([
            'success' => true,
            'html' => view('public.layouts.track_order')
                ->with('order', $order)
                ->with('delivery_info', $this->prepareDeliveryInformation($order))
                ->render()
        ]);
    }

    public function adminDeleteAction($id){
        $order = Order::find($id);

        if(empty($order)){
            return response()->json(['result' => 'error', 'message' => trans('locale.Order not found')], 200);
        }

        // Сохранение действия
        Action::deleteEntity($order);

        $order->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.Order #:id deleted', ['id' => $id])], 200);
    }
}
