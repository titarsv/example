<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Session;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    protected $table = 'cart';
    protected $fillable = [
        'user_id',
        'user_ip',
        'session_id',
        'products',
        'total_quantity',
        'total_price',
        'coupon_sale',
        'crypto_sale',
        'total_sale',
        'amount_sale',
        'user_data',
        'cart_data'
    ];

	public function user(){
		return $this->hasOne('App\Models\User', 'id', 'user_id');
	}

    public function coupon(){
        return $this->hasOne(\Modules\Coupons\Models\Coupon::class, 'id', 'coupon_id');
    }

	public function getSaleAttribute(){
	    $sale = 0;

	    return $sale;
    }

    public function getShippingAttribute(){
        return $this->total_price - $this->total_sale >= 100 ? 0 : 6;
    }

    /**
     * Получение корзины пользователя
     *
     * @param bool $create - Создавать новую корзину или нет
     * @return mixed
     */
	public function current_cart($create = true){
		$cart_id = Cookie::get('cart_id');
		$user = Sentinel::check();

		if($user) {
			$user_id = $user->id;
			$cart = $this->where('user_id', $user_id)->first();

			if(!empty($cart_id) && !empty($cart) && $cart->id != $cart_id){
				$saved_cart = $this->where('id', $cart_id)->where('total_quantity', '>', 0)->first();
				if(!empty($saved_cart) && !empty($saved_cart->total_quantity)){
					$products = (array)json_decode($cart->products, true) + (array)json_decode($saved_cart->products, true);
					$cart->products = json_encode($products);
					$cart->total_quantity += $saved_cart->total_quantity;
					$cart->total_price += $saved_cart->total_price;
					$cart->updated_at = date('Y-m-d H:i:s');
					if($cart->session_id != Session::getId()){
						$cart->session_id = Session::getId();
					}
					$cart->save();
				}
			}

			if(!is_null($cart) && $cart->session_id != Session::getId())
				$cart->update(['session_id' => Session::getId(), 'updated_at' => date('Y-m-d H:i:s')]);
		} else {
			$user_id = 0;
			if(!empty($cart_id)) {
				$cart = $this->where('id', $cart_id)->where('user_id', 0)->first();
			}else{
				$cart = $this->where('session_id', Session::getId())->first();
			}
		}

		if(is_null($cart) && $create) {
			$cart = $this->create_cart($user_id);
		}

		Cookie::queue('cart_id', $cart->id, 2628000, null, null, false, false);

		if(!empty($cart->products)){
            $products = json_decode($cart->products);
            if(!empty($products)) {
                foreach ($products as $product_id => $product_data) {
                    $product = Product::find($product_id);
                    if (empty($product) || $product->stock < 1) {
                        unset($products->$product_id);
                        $update = true;
                    }
//                elseif($product->stock < $product_data->quantity){
//                    $products->$product_id->quantity = $product->stock;
//                    $update = true;
//                }
                }
            }

            if(!empty($update)){
                $cart->products = json_encode($products);
                $cart->update_cart();
            }
		}

		return $cart;
	}

    /**
     * Создание новой корзины
     *
     * @param $user_id
     * @return mixed
     */
	public function create_cart($user_id){

		$id = $this->insertGetId([
			'user_id' => $user_id,
			'session_id' => Session::getId(),
			'products' => null,
			'total_quantity' => 0,
			'total_price' => 0,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s')
		]);

		return $this->where('id', $id)->first();
	}

    /**
     * Обновление корзины
     */
    public function update_cart($payment_method = null){
        $products = json_decode($this->products, true);

        $total_quantity = 0;
        $total_price = 0;
        $total_sale = 0;
        $salable = 0;

        if(!module_active('coupons')){
            // Отключённый модуль не должен применять скидку купона, даже если
            // в корзине уже сохранён coupon_id с момента, когда модуль был включён.
            $this->coupon_id = null;
        }

        if(!empty($this->coupon_id) && !empty($this->coupon)){
            $coupon = $this->coupon;

            if(!$coupon->status ||$coupon->used || $this->total_price < $coupon->min_total || (!empty($coupon->from) && strtotime($coupon->from) > time()) || (!empty($coupon->to) && strtotime($coupon->to) < time())){
                $this->coupon_id = null;
                unset($coupon);
            }
        }

        $coupon_products_scope = [];
        if(!empty($coupon) && !empty($coupon->scope)){
            if($coupon->scope->type == 'products'){
                $coupon_products_scope = $coupon->scope->ids;
            }elseif($coupon->scope->type == 'categories'){
                $scope_categories_products = DB::table('product_categories')
                    ->select('product_id')
                    ->whereIn('category_id', $coupon->scope->ids)
                    ->groupBy('product_id')
                    ->get();

                if(!empty($scope_categories_products))
                    $coupon_products_scope = $scope_categories_products->pluck('product_id')->toArray();
            }
        }

        if(!empty($products)){
            foreach($products as $product_id => $data){
                $total_quantity += $data['quantity'];
                $total_price += (float)$data['price'] * (int)$data['quantity'];
                if(isset($coupon) && !empty($coupon->product_id) && (empty($coupon->without_sale) || empty($data['is_sale_price']))){
                    $ids = explode('_', $product_id);
                    if($coupon->product_id == $ids[0]){
                        if($coupon->used){
                            $data['sale'] = 0;
                        }elseif(!empty($coupon->price)){
                            $data['sale'] = $coupon->price;
                        }elseif(!empty($coupon->percent)){
                            $data['sale'] = (float)$data['price'] * ($coupon->percent / 100);
                        }
                    }

                    if($coupon->disposable)
                        $total_sale += (float)$data['sale'];
                    else
                        $total_sale += (float)$data['sale'] * (int)$data['quantity'];

                    $products[$product_id]['sale'] = $data['sale'];
                }else{
                    $total_sale += (float)$data['sale'] * (int)$data['quantity'];
                    if(isset($coupon) && (empty($coupon->without_sale) || empty($data['is_sale_price'])) && (empty($coupon_products_scope) || in_array($product_id, $coupon_products_scope))){
                        $salable += (float)$data['price'] * (int)$data['quantity'];
                    }
                }
            }
        }

        if(isset($coupon) && empty($coupon->product_id) && !empty($salable)){
            if(!empty($coupon->price)){
                $coupon_sale = round($coupon->price <= $salable ? $coupon->price : $salable);
            }elseif(!empty($coupon->percent)){
                $coupon_sale = round($salable * ($coupon->percent / 100));
            }
            $total_sale += $coupon_sale;
        }

        $payment_method = !empty($payment_method) ? $payment_method : Cookie::get('payment_method');
        if(empty($payment_method)){
            $payment_method = 'btcpay';
        }
        $crypto_sale = 0;
        if (in_array($payment_method, ['btcpay', 'mycryptocheckout'])) {
            $crypto_sale = round(($this->total_price - $this->coupon_sale) * 0.1);
            $total_sale += $crypto_sale;
        }
        if($this->total_price > 149){
            $amount_sale = round($this->total_price * 0.1);
            $total_sale += $amount_sale;
        }else{
            $amount_sale = 0;
        }

        if($total_price < $total_sale){
            $total_sale = $total_price;
        }

        $this->update(['total_quantity' => $total_quantity, 'total_price' => $total_price, 'total_sale' => $total_sale, 'coupon_sale' => !empty($coupon_sale) ? $coupon_sale : 0, 'crypto_sale' => $crypto_sale, 'amount_sale' => $amount_sale, 'products' => json_encode($products)]);

        return $this;
    }

    public function full_cart_update(){
        $products = json_decode($this->products, true);
        if(is_null($products))
            $products = [];

        foreach ($products as $id => $data){
            $ids = explode('_', $id);
            $product_id = (int)$ids[0];
            $variation = isset($ids[1]) ? (int)$ids[1] : 0;
            $price = $this->get_product_price($product_id, $variation);
            $products[$id]['price'] = $price['price'];
            $products[$id]['sale'] = $price['sale'];
            $products[$id]['sale_percent'] = $price['sale_percent'];
        }

        $this->update(['products' => json_encode($products)]);
        $this->update_cart();
    }

    /**
     * Получение продуктов из корзины
     *
     * @return array
     */
    public function get_products(){
        $current_cart = $this->current_cart();
        $products_in_cart = json_decode($current_cart->products, true);
        $products = [];

        if(!is_null($products_in_cart)) {
            foreach ($products_in_cart as $product_id => $data) {
                $variation_attrs = [];
                if(!empty($data['variation'])){
                    $v = new Variation();
                    $variation = $v->find($data['variation']);
                    if(!empty($variation)){
                        $values = $variation->attribute_values;
                        foreach($values as $value){
                            $attr = $value->attribute;
                            if(!isset($variation_attrs[$attr->name])){
                                $variation_attrs[$attr->name] = $value->name . (!empty($attr->unit) ? $attr->unit : '');
                            }
                        }
                    }
                }

                $ids = explode('_', $product_id);
                $products[$product_id] = [
                    'product'   => Product::where('id', $ids[0])->with('attributes.value')->first(),
                    'quantity'  => $data['quantity'],
                    'variations'  => $variation_attrs,
                    'price'  => $data['price'],
                    'sale' => $data['sale'],
                    'sale_percent' => $data['sale_percent']
                ];
            }
        }

        return $products;
    }

    /**
     * Код товара
     *
     * @param $product_id
     * @param $variation
     * @return string
     */
    private function get_product_code($product_id, $variation){
        if(!empty($variation)){
            $product_id .= '_'.$variation;
        }
        return $product_id;
    }

    /**
     * Добавление товара в корзину
     *
     * @param $product_id
     * @param int $quantity
     * @param $variation
     */
    public function add_product($product_id, $quantity = 1, $variation = null){
        $product_code = $this->get_product_code($product_id, $variation);

        $products = json_decode($this->products, true);
        if(is_null($products))
            $products = [];

        if(array_key_exists($product_code, $products)){
            $products[$product_code] = ['quantity' => $products[$product_id] + $quantity];
        } else {
            $products[$product_code] = ['quantity' => $quantity];
            if(!empty($variation)){
                $products[$product_code]['variation'] = $variation;
            }
            $price = $this->get_product_price($product_id, $variation);
            $products[$product_code]['price'] = $price['price'];
            $products[$product_code]['sale'] = $price['sale'];
            $products[$product_code]['sale_percent'] = $price['sale_percent'];
            $products[$product_code]['is_sale_price'] = $price['is_sale_price'];
        }

        $this->update(['products' => json_encode($products)]);
        $this->update_cart();
    }

    /**
     * Удаление товара из корзины
     *
     * @param $product_code
     */
    public function remove_product($product_code){
        $products = json_decode($this->products, true);
        unset($products[$product_code]);

        $this->update(['products' => json_encode($products)]);
        $this->update_cart();
    }

    /**
     * Изменение колличества товара в корзине на указанную величину
     *
     * @param $product_id
     * @param $delta
     * @param $variation
     */
    public function increment_product_quantity($product_id, $delta, $variation = 0){
        $product_code = $this->get_product_code($product_id, $variation);

        if ($this->product_isset($product_code)) {

            $products = json_decode($this->products, true);
            $products[$product_code]['quantity'] = $products[$product_code]['quantity'] + $delta;
            $this->products = json_encode($products);
            $this->update_cart();

        } elseif ($delta > 0) {
            $this->add_product($product_id, $delta, $variation);
        }
    }

    /**
     * Изменение колличества товара в корзине
     *
     * @param $product_id
     * @param $quantity
     * @param array $variations
     */
    public function update_product_quantity($product_id, $quantity, $variations = []){
        $product_code = $this->get_product_code($product_id, $variations);

        if ($quantity <= 0) {
            $this->remove_product($product_code);
        }

        if ($this->product_isset($product_code)) {
            $products = json_decode($this->products, true);
            $products[$product_code]['quantity'] = $quantity;
            $this->update(['products' => json_encode($products)]);
            $this->update_cart();
        } else {
            $this->add_product($product_id, $quantity, $variations);
        }
    }

    /**
     * Проверка наличия товара в корзине
     *
     * @param $product_code
     * @return bool
     */
    public function product_isset($product_code){
        $products = json_decode($this->products, true);

        if(is_null($products)) {
            return false;
        } else {
            return array_key_exists($product_code, $products);
        }
    }

    /**
     * Стоимость вариативного товара
     *
     * @param $product_id
     * @param $variation
     * @return mixed
     */
    public function get_product_price($product_id, $variation){
        $product = Product::find($product_id);
        if(empty($product)){
        	return 0;
        }

        $price = $product->price;

        $variation = $product->variations()->where('id', $variation)->first();
        if(!empty($variation)){
            $price = $variation->price;
        }

        return $this->get_sale_price($product, $price);
    }

    /**
     * Стоимость с учётом скидки
     *
     * @param $product
     * @param $price
     * @return array
     */
    public function get_sale_price($product, $price){
        $s = 0;
        $sale = 0;
        if(empty($product->sale)){
            $user = Sentinel::check();
            if(!empty($user)){
                $user = User::find($user->id);

                if($user) {
                    $s = $user->sale();
                    if(!empty($s)){
                        $sale = $price * $s / 100;
                        $price = $price - $sale;
                    }
                }
            }
        }

        return ['price' => $price, 'sale' => $sale, 'sale_percent' => $s, 'is_sale_price' => $product->sale];
    }

	/**
	 * Получение претендентов на рассылку уведомлений о неоконченной покупке
	 *
	 * @return mixed
	 */
	public function get_reminders(){
		$this->where('total_quantity', 0)->where(function ($query) {
			$query->where('updated_at', '<', Carbon::now()->subDays(3))
			      ->orWhere('updated_at', null);
		})->delete();
		$reminders = $this->where('user_id', '>', 0)->where('total_quantity', '>', 0)->where('updated_at', '<', Carbon::now()->subDays(3))->where(function ($query) {
			$query->where('user_data', 'NOT LIKE', '%reminder_send%')
			      ->orWhere('user_data', null);
		})->limit(15)->get();

		return $reminders;
	}

	/**
	 * Сохранене статуса отправки уведомления о неоконченной покупке
	 */
	public function reminder_success(){
		$user_data = json_decode($this->user_data, true);
		if(!is_array($user_data)){
			$user_data = ['statuses' => []];
		}

		if(($key = array_search('reminder_showed', $user_data)) !== FALSE){
			unset($user_data[$key]);
		}

		$user_data['statuses'][] = 'reminder_send';

		$this->update(['user_data' => json_encode($user_data)]);
	}

    /**
     * Добавление скидочного купона
     *
     * @param $id
     * @return $this
     */
	public function addCoupon($id){
        if(!module_active('coupons')){
            return $this;
        }

        $this->coupon_id = $id;
        $this->update_cart();

        return $this;
    }

    /**
     * Удаление скидочного купона
     *
     * @return $this
     */
    public function removeCoupon(){
        $this->coupon_id = null;
        $this->update_cart();

        return $this;
    }

    public function getRecommended(){
        $products = $this->get_products();
        $recommendations = [];
        foreach($products as $product){
            $similar_products = $product['product']->similarProducts();
            if(!empty($similar_products)) {
                foreach ($product['product']->similarProducts() as $similar) {
                    $recommendations[] = $similar;
                }
            }
        }

        return collect($recommendations);
    }
}
