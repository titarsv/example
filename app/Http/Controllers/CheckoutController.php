<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Modules\Coupons\Models\Coupon;
use Modules\Notifications\Models\Sendpulse;
use Modules\Notifications\Services\TelegramNotifierService;
use App\Models\Product;
use App;

class CheckoutController extends Controller
{
    /**
     * Оформление заказа
     *
     * @param $data
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\View\View|object
     */
    public function showAction($data){
        $request = $data->request;
        if($request->method() == 'POST'){
            return $this->createOrder($data);
        }

        $cart = new Cart;
        $cart = $cart->current_cart();
        $cart->update_cart();

        $settings = new Setting();
        $payments = [];
        foreach($settings->get_setting('payment_methods') as $payment_method){
            $payments[$payment_method] = $this->getPaymentMethod($payment_method);
        }

        $payment_method = Cookie::get('payment_method');
        if(empty($payment_method)){
            $payment_method = array_key_first($payments);
        }

        return view('public.checkout')
            ->with('cart', $cart)
            ->with('delivery_cost', 0)
            ->with('seo', $data->seo)
            ->with('methods', $settings->get_setting('delivery_methods'))
            ->with('payments', $payments)
            ->with('payment_method', $payment_method);
    }

    private function getPaymentMethod($payment_method){
        $settings = new Setting();
        $data = [
            'name' => $settings->get_setting('payment_'.$payment_method.'_name_'.app()->getLocale())
        ];

        if($payment_method == 'mycryptocheckout'){
            $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
            $mcc_account_data = $mcc->account()->data;
            $currencies = $mcc->currencies();
            $wallets = $mcc->wallets();
            $data['currencies'] = [];
            foreach($wallets as $wallet){
                if(!isset($data['currencies'][$wallet->currency_id])){
                    $currency = $currencies->get($wallet->currency_id);
                    $data['currencies'][$wallet->currency_id] = $currency->data->name . ' (' . $wallet->currency_id .  ')';
                }
            }
        }

        return $data;
    }

    /**
     * Создание заказа
     *
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder($data){
        $request = $data->request;
        $order = new Order();
        $cart = new Cart();
        $users = new User();
        $cart = $cart->current_cart();

        if(!$cart->total_quantity){
            return response()->json(['error' => ['cart' => __('There are no products in the cart!')]]);
        }

        $rules = [
//            'phone'     => 'required|regex:/^[0-9\-! ,\'\"\/+@\.:\(\)]+$/',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
            'payment' => 'required',
//            'delivery' => 'required',
        ];

        $messages = [
//            'phone.required'    => 'You haven\'t provided a phone number!',
//            'phone.regex'       => 'Incorrect phone number!',
            'email.required'        => 'You have not specified an email!',
            'email.regex'           => 'Incorrect email!',
            'first_name.required'   => 'You haven\'t specified a first name!',
            'last_name.required'   => 'You haven\'t specified a last name!',
            'payment.required'      => 'Payment method not selected!',
//            'delivery.required'     => 'Delivery method not selected!'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if($validator->fails() || is_null($cart)){
            $errors = is_null($cart) ? __('Your cart is empty!') : $validator->messages();
            return response()->json(['error' => $errors]);
        }

        $errors = $this->validateFields($request->all());
        if($errors){
            return response()->json(['error' => $errors]);
        }

        if(empty($request->email)){
            $request->email = 'email'.rand(0, 1000000).'@placeholder.com';
            while($users->checkIfUnregistered($request->phone, $request->email)){
                $request->email = 'email'.rand(0, 1000000).'@placeholder.com';
            }
        }

        $user = Sentinel::check();

        if(!$user){
            $existed_user = $users->checkIfUnregistered($request->phone, $request->email);

            if(!is_null($existed_user)) {
                $user = $existed_user;
            } else {
                $register = new AuthenticationController();
                $user = $register->storeAsUnregistered($request);
            }
        }

        $user = User::find($user->id);

        $delivery_info = [
            'delivery_cost' => $cart->total_price - $cart->total_sale > 100 ? 0 : 6,
            'city' => $request->city,
            'street' => $request->address,
            'index' => $request->index,
        ];

        $cart = $cart->update_cart();

        if(!empty($request->promo)){
            $promo = [];
            foreach($request->promo as $p){
                $promo[] = trim($p);
            }
        }else{
            $promo = null;
        }

        $products = json_decode($cart->products, true);
        foreach($products as $i => $product){
            $products[$i]['price'] = $products[$i]['price'] ;
            $products[$i]['sale'] = $products[$i]['sale'];
        }

        $data = [
            'user_id'   => $user->id,
            'products'  => json_encode($products),
            'total_quantity'    => $cart->total_quantity,
            'total_price'       => $cart->total_price,
            'coupon_sale'       => $cart->coupon_sale,
            'crypto_sale'       => $cart->crypto_sale,
            'amount_sale'       => $cart->amount_sale,
            'total_sale'       => $cart->total_sale,
            'user_info'         => json_encode([
                'name'  => !empty($request->first_name) && !empty($request->last_name) ? $request->first_name . ' ' . $request->last_name : $user->first_name . ' ' . $user->last_name,
                'email' => !empty($request->email) ? $request->email : $user->email
            ], JSON_UNESCAPED_UNICODE),
            'delivery'  => json_encode($delivery_info, JSON_UNESCAPED_UNICODE),
            'payment'   => $request->payment,
            'status_id' => 0,
            'coupon_id' => $cart->coupon_id,
            'created_at' => Carbon::now()
        ];

        $id = $order->insertGetId($data);
        if(!empty($cart->coupon) && $cart->coupon->disposable){
            $cart->coupon->used = 1;
            $cart->coupon->save();
        }
        $order = Order::find($id);

        if($request->payment == 'mycryptocheckout' && !empty($order->full_price) && !empty($request->cryptocurrency) && in_array($request->cryptocurrency, ['BTC', 'ETH', 'LTC', 'XMR', 'MATIC', 'USDT_TRON', 'XRP'])){
            $mcc = new \App\Services\MyCryptoCheckout\LaravelAPI();
            $currency_id = $request->cryptocurrency;

            // Отправляем на сервер MCC для расчета суммы в крипте и получения адреса
            try {
                $mcc->createPayment($order, $currency_id);
                $this->sendOrderMails($id);
                if(!env('APP_DEBUG')){
                    $this->sendToTelegram($order);
                }
                return response()->json(['success' => 'redirect', 'order_id' => $id]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage(), 'order_id' => $id]);
            }
        }elseif($request->payment == 'btcpay' && !empty($order->full_price)){
            try {
                $btcPay = new \App\Services\BTCPayService();
                $invoice = $btcPay->createInvoice($order->full_price, 'GBP', $id);
                $this->sendOrderMails($id);

                return response()->json(['success' => 'redirect', 'url' => $invoice['checkoutLink']]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        }else{
            $order->update(['status_id' => 1]);
            $this->sendOrderMails($id);
            if(!env('APP_DEBUG')){
                $this->sendToTelegram($order);
            }

            return response()->json(['success' => 'redirect', 'order_id' => $id]);
        }
    }

    public function sendOrderMails($order_id){
        $cart = new Cart();
        $order = Order::find($order_id);
        $order->update(['status_id' => 1]);
        $order_user = json_decode($order->user_info, true);
        $cart = $cart->current_cart();
        $cart->current_cart()->delete();

        // Dispatch email sending to queue with delay
        \App\Jobs\SendOrderEmailsJob::dispatch($order_id, $order_user, $order)
            ->delay(now()->addMinutes(1)); // Отложенная отправка на 1 минуту
    }

    private function sendToTelegram(Order $order){
        $user = json_decode($order->user_info);
        $delivery = $order->getDeliveryInfo();
        $products = $order->getProducts();

        $text = __("New order on the website").": ".base_url('/')."/admin/orders/edit/".$order->id."\n";
        $text .= __("Order amount").": £".((float)$order->total_price - (float)$order->total_sale)."\n";
        $text .= __("Buyer's contacts").": ".(isset($user->name) ? $user->name : '')." ".(isset($user->phone) ? $user->phone : '')."\n";
        $text .= __("Delivery").": ".
            (!empty($delivery['method']) ? $delivery['method']." " : "").
            (!empty($delivery['region']) ? $delivery['region']." " : "").
            (!empty($delivery['city']) ? $delivery['city']." " : "").
            $order->getAddressAttribute()."\n";
        $text .= __("Payment").": ".$order->getPaymentMethodAttribute()."\n";
        $text .= __("The following items have been ordered").":\n";

        foreach($products as $product_id => $item) {
            $text .= $item['product']->name." (".$item['quantity'].__("pcs").".)\n";
        }

        app(TelegramNotifierService::class)->broadcast($text);
    }

    /**
     * Получене данных для Liqpay
     *
     * @param $order
     * @return \Illuminate\Http\JsonResponse
     * @throws \League\Flysystem\Exception
     */
	public function getLiqpayData($order){
		$public_key = config('liqpay.public_key');
		$private_key = config('liqpay.private_key');
		$liqpay = new LiqPay($public_key, $private_key);
		$checkout = $liqpay->cnb_form([
			'action'    => 'pay',
			'amount'    => $order->total_price - $order->total_sale,
			'currency'  => 'UAH',
			'description'   => __('Payment for the order №') . $order->id . __(' on the website').' '.env('APP_NAME'),
			'order_id'  => $order->id,
			'sandbox'   => 0,
			'version'   => 3,
			'result_url' => url('/?order_id=' . $order->id)
		]);

		return response()->json(['success' => 'liqpay', 'liqpay' => $checkout, 'order_id' => $order->id]);
	}

    /**
     * Подгрузка различных темплейтов в зависимости от выбранного способа доставки
     *
     * @param Request $request
     * @param Newpost $newpost
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function delivery(Request $request, Newpost $newpost){
        if (!is_null($request->cookie('current_order_id'))) {
            $current_order_id = $request->cookie('current_order_id');
        } elseif ($request->order_id) {
            $current_order_id = $request->order_id;
        }else {
            $current_order_id = 0;
        }

        $cart = new Cart();
        $cart = $cart->current_cart();

        $city = $request->city;

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
                'delivery' => view('public.checkout.newpost', [
                    'regions' => $regions,
                    'region_id' => $region_id,
                    'cities' => $cities,
                    'city_id' => $city_id,
                    'warehouses' => $warehouses,
                    'current_order_id' => $current_order_id,
                    'lang' => App::getLocale()
                ])->render(),
                'confirmation' => view('public.checkout.confirmation', [
                    'cart' => $cart,
                    'delivery_cost' => 0,
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
                'delivery' => view('public.checkout.justin', [
                    'regions' => $regions,
                    'region_id' => $region_id,
                    'cities' => $cities,
                    'city_id' => $city_id,
                    'warehouses' => $warehouses,
                    'current_order_id' => $current_order_id,
                    'subtotal' => 0,
                    'total' => 0,
                    'lang' => App::getLocale()
                ])->render(),
                'confirmation' => view('public.checkout.confirmation', [
                    'cart' => $cart,
                    'delivery_cost' => 0,
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
                'delivery' => view('public.checkout.' . $request->delivery, [
                    'current_order_id' => $current_order_id,
                    'region' => $region_name
                ])->render(),
                'confirmation' => view('public.checkout.confirmation', [
                    'cart' => $cart,
                    'delivery_cost' => 0,
                ])->render()
            ]);
        }
    }

    /**
     * Валидация полей доставки
     *
     * @param $data
     * @return mixed
     */
    public function validateFields($data)
    {
        $errors = [];

        $rules['payment'] = 'required|in:mycryptocheckout,card,btcpay';
        $messages['payment.required'] = __('Payment method not selected!');
        $messages['payment.in'] = __('Incorrect payment method selected!');

        $validator = Validator::make($data, $rules, $messages);

        if($validator->fails()){
            $errors = array_merge($errors, $validator->messages()->toArray());
        }

        if (!empty($errors))
            return $errors;

        return false;
    }

    /**
     * Загрузка списка городов Новой Почты
     *
     * @param Request $request
     * @param Newpost $newpost
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCities(Request $request, Newpost $newpost){
        if(!is_object($request)){
            return response()->json(['error' => __('There was an error loading cities. Please try again!')]);
        }
        $region = $newpost->getRegionRef($request->region_id);

        if (!empty($region)) {
            $cities = $newpost->getCities($region->region_id);
        } else {
            return response()->json(['error' => __('There was an error loading cities. Please try again!')]);
        }

        if ($cities) {
            return response()->json(['success' => $cities]);
        } else {
            return response()->json(['error' => __('There was an error loading cities. Please try again!')]);
        }
    }

    /**
     * Загрузка списка отделений Новой Почты
     *
     * @param Request $request
     * @param Newpost $newpost
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarehouses(Request $request, Newpost $newpost){
        $city = $newpost->getCityRef($request->city_id);

        if(!is_null($city) && isset($city->city_id)){
            $warehouses = [];
            foreach($newpost->getWarehouses($city->city_id) as $warehouse){
                $warehouses[$warehouse->id] = app()->getLocale() == 'ua' ? $warehouse->address_ua : $warehouse->address_ru;
            }
        }else{
            return response()->json(['error' => __('There was an error loading branches. Please try again!')]);
        }

        if($warehouses){
            $branches = [];
            foreach(Warehouse::where('city_id', $request->city_id)->get() as $warehouse){
                $branches[] = [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'address' => $warehouse->address,
                    'schedule' => $warehouse->schedule
                ];
            }
            return response()->json(['success' => $warehouses, 'msg' => __('Select a branch'), 'branches' => $branches]);
        }else{
            return response()->json(['error' => __('There was an error loading branches. Please try again!')]);
        }
    }

    /**
     * Загрузка списка городов Justin
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJustinCities(Request $request){
        $justin = new Justin();

        if(!is_object($request)){
            return response()->json(['error' => __('There was an error loading cities. Please try again!')]);
        }
        $cities = $justin->getCities($request->region_id);

        if($cities){
            return response()->json(['success' => $cities]);
        }else{
            return response()->json(['error' => __('There was an error loading cities. Please try again!')]);
        }
    }

    /**
     * Загрузка списка отделений Justin
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJustinWarehouses(Request $request){
        $justin = new Justin();
        $warehouses = $justin->getWarehouses($request->city_id);

        return response()->json(['success' => $warehouses]);
    }

    public function applyCoupon(Request $request, Coupon $coupons){
        if(!module_active('coupons')){
            return response()->json(['result' => 'error', 'msg' => __('Invalid promo code')]);
        }

        if(!empty($request->code)){
            $coupon = $coupons->where('code', $request->code)->where('used', 0)->where('status', 1)->first();

            if(!empty($coupon)){
                $cart = new Cart();
                $cart = $cart->current_cart();

                if((!empty($coupon->user_id) && $coupon->user_id != $cart->user_id) || $coupon->used || (!empty($coupon->shelf_life) && strtotime($coupon->shelf_life) < time())){
                    return response()->json(['result' => 'error', 'msg' => __('Invalid promo code')]);
                }

                $cart = $cart->addCoupon($coupon->id);
                $cart->update_cart();

                return response()->json(['result' => 'success', 'cart' => [
                    'count' => $cart->total_quantity,
                    'subtotal' => '£'.number_format($cart->total_price, 0, '.', ' '),
                    'sale' => '- £'.number_format($cart->total_sale, 0, '.', ' '),
                    'coupon_sale' => '- £'.number_format($cart->coupon_sale, 0, '.', ' '),
                    'total' => '£'.number_format($cart->total_price - $cart->total_sale + $cart->shipping, 0, '.', ' '),
                    'total_without_shipping' => '£'.number_format($cart->total_price - $cart->total_sale, 0, '.', ' '),
                    'checkout_prices' => view('public.layouts.checkout_prices')->with('cart', $cart)->render(),
                ]]);
            }
        }else{
            $cart = new Cart();
            $cart = $cart->current_cart();
            $cart = $cart->removeCoupon();
            $cart->update_cart();

            return response()->json(['result' => 'success', 'cart' => [
                'count' => $cart->total_quantity,
                'subtotal' => '£'.number_format($cart->total_price, 0, '.', ' '),
                'sale' => '- £'.number_format($cart->total_sale, 0, '.', ' '),
                'coupon_sale' => '- £'.number_format($cart->coupon_sale, 0, '.', ' '),
                'total' => '£'.number_format($cart->total_price - $cart->total_sale + $cart->shipping, 0, '.', ' '),
                'total_without_shipping' => '£'.number_format($cart->total_price - $cart->total_sale, 0, '.', ' '),
                'checkout_prices' => view('public.layouts.checkout_prices')->with('cart', $cart)->render(),
            ]]);
        }

        return response()->json(['result' => 'error', 'msg' => __('Invalid promo code')]);
    }

    public function searchCities(Request $request){
        $newpost = new Newpost();

        $cities = [];
        foreach($newpost->findCities($request->search) as $city){
            $cities[$city->id] = app()->getLocale() == 'ua' ? $city->name_ua : $city->name_ru;
        }

        return response()->json(['result' => 'success', 'cities' => $cities]);
    }

    public function changePayment(Request $request){
        $cart = new Cart();
        $cart = $cart->current_cart();

        if(!empty($cart->cart_data)){
            $cart_data = json_decode($cart->cart_data, true);
        }else{
            $cart_data = [];
        }

        $cart_data['payment'] = $request->payment;

        $cart->update(['cart_data' => json_encode($cart_data)]);

        return response()->json(['result' => 'success', 'cart' => [
            'count' => $cart->total_quantity,
            'total' => $cart->total_price,
            'sale' => $cart->total_sale,
            'coupon_sale' => $cart->coupon_sale,
            'payment_sale' => $cart->payment_ale,
            'html' => view('public.checkout.confirmation')
                ->with('cart', $cart)->render()
        ]]);
    }

    public function paypalOrderCapture(Request $request){
        $data = json_decode($request->getContent(), true);
        $orderId = $data['orderId'];
        $paypalClient = new PayPalClient;
        $token = $paypalClient->getAccessToken();
        $paypalClient->setAccessToken($token);
        $result = $paypalClient->capturePaymentOrder($orderId);

        try{
            if($result['status'] === "COMPLETED"){
                $order = Order::where('user_info', 'like', '%"vendor_order_id":"'.$orderId.'"%')->first();;
                $order->status_id = 2;
                $order->save();
                $result['order_id'] = $order->id;

                if(module_active('notifications')){
                    $sendpulse = new Sendpulse();
                    $sendpulse->orderHasBeenPaid($order->user, $order);
                }
            }
        }catch(Exception $e){
            Log::error('Paypal exception:', ['error' => $e]);
        }

        return response()->json($result);
    }

    public function abandonedCartAction(Request $request){
        if(module_active('notifications')){
            $cart = new Cart();
            $sendPulse = new Sendpulse();
            $sendPulse->abandonedCart($request->name, $request->phone, $request->email, $cart->current_cart()->get_products(), $cart->total_price - $cart->payment_sale - $cart->coupon_sale, $cart->total_sale + $cart->coupon_sale + $cart->payment_sale);
        }

        return response()->json(['result' => 'success']);
    }

    public function getConfirmationsAction(){
        $cart = new Cart;

        return response()->json([
            'result' => 'success',
            'confirmation' => view('public.checkout.confirmation')->with('cart', $cart->current_cart())->render(),
            'confirmation_mob' => view('public.checkout.confirmation_mob')->with('cart', $cart->current_cart())->render(),
        ]);
    }

    /**
     * Обновление способа оплаты
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentMethodAction(Request $request)
    {
        $method = $request->input('method');

        // Сохраняем выбранный способ оплаты в куки
        $cookie = Cookie::make('payment_method', $method, 60*24*30); // 30 дней

        $cart = new Cart();
        $currentCart = $cart->current_cart();
        $currentCart->update_cart($method);

        // Возвращаем обновленные цены
        return response()
            ->json([
                'result' => 'success',
                'checkout_prices' => view('public.layouts.checkout_prices')
                    ->with('cart', $currentCart)
                    ->render()
            ])
            ->withCookie($cookie);
    }

}
