<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Models\ProductsCart;
use Modules\Delivery\Models\Newpost;
use App\Models\Cart;
use App\Models\User;

class CartController extends Controller
{
    public function showAction($data){
        $cart = new Cart;
        $current_cart = $cart->current_cart();

        return view('public.cart')
            ->with('seo', $data->seo)
            ->with('recommendations', $current_cart->getRecommended())
            ->with('cart', $current_cart);
    }

    /**
     * Обновление продукта в корзине
     *
     * @param Request $request
     * @param Cart $cart
     * @return mixed
     */
    public function updateCart(Request $request, Cart $cart){
        $product_id = $request->product_id;

        $current_cart = $cart->current_cart();

        if(empty($request->variation)){
            $variation = 0;
        }else{
            $variation = $request->variation;
        }

        if($request->action == 'add'){
            $product_quantity = $request->quantity ? $request->quantity : 1;
            $current_cart->increment_product_quantity($product_id, $product_quantity, $variation);
        }elseif($request->action == 'remove'){
            $current_cart->remove_product($product_id, $variation);
        }elseif($request->action == 'update'){
            $product_quantity = $request->quantity ? $request->quantity : 0;
            $current_cart->update_product_quantity($product_id, $product_quantity, $variation);
        }

        $current_cart = $current_cart->update_cart();

        return response()->json([
            'count' => $current_cart->total_quantity,
            'total' => $current_cart->total_price,
            'coupon_sale' => $current_cart->coupon_sale,
            'html' => $this->getCart()->render(),
            'checkout_prices' => view('public.layouts.checkout_prices')->with('cart', $current_cart)->render(),
            'checkout_spend' => view('public.layouts.checkout_spend')->with('cart', $current_cart)->render(),
        ]);
    }

    /**
     * Корзина пользователя
     *
     * @return $this
     */
    public function getCart(){
        $cart = new Cart;
        $current_cart = $cart->current_cart();

        $popular_products = Product::orderBy('popularity', 'desc')->where('visible', 1)->limit(8)->get();

        return view('public.layouts.cart')
            ->with('cart', $current_cart)
            ->with('popular_products', $popular_products);
    }

    /**
     * @param Request $request
     * @return array|bool
     */
    public function update(Request $request){
        $user_cart_id = Session::get('user_id');

        $current_cart = Cart::where('user_id', $user_cart_id)->first();

        if(is_null($current_cart)){
            return false;
        }
        $current_cart->products_cart()->delete();
        if(!empty($request['cart'])){
            foreach($request['cart'] as $product){
                if($product['product_quantity'] <= 0){
                    continue;
                }
                $new_products_cart = new ProductsCart($product);
                $current_cart->products_cart()->save($new_products_cart);
            }
        }

        $products_in_cart = $current_cart->products_cart()->get();
        $products_quantity = $products_in_cart->sum('product_quantity');
        $products_array = $products_in_cart->lists('product_quantity' , 'product_id')->toArray();

        $products_sum = 0;
        foreach($products_in_cart as $products){
            $products_sum += $products->product->price * $products_array[$products->product->id];
        }

        $current_cart->update(['products_quantity' => $products_quantity, 'products_sum' => $products_sum]);
//        $current_cart->update(['products_quantity' => $request['products_quantity']]);
        return ['products_quantity' => $request['products_quantity'], 'sum' => $request['sum']];
    }

    /**
     * @param $user_cart_id
     * @return mixed
     */
    public function getCartProducts($user_cart_id){
        $cart = Cart::where('user_id',$user_cart_id)->first();

        return $cart;
    }

    /**
     * Метод передает корзину юзеру
     * @param $user_id
     * @return array
     */
    public static function cartToUser($user_id){
//        $user_cart_id = Session::get('user_id');
//        $current_cart_from_sess = Cart::where('user_id', $user_cart_id)->first();
        $current_cart_from_sess = Cart::where('session_id', Session::getId())->first();
        $current_cart = [];
        if(!is_null($current_cart_from_sess)) {
            Cart::where('user_id', $user_id)->delete();
            $current_cart_from_sess->update(['user_id' => $user_id]);
            $current_cart = $current_cart_from_sess;
        }
        $current_cart_from_user = Cart::where('user_id', $user_id)->first();
        if(!is_null($current_cart_from_user)) {
//            $current_cart_from_user->update(['user_id' => $user_id]);
            $current_cart = $current_cart_from_user;
            $user_cart_id = $current_cart->user_cart_id;
            Session::put('user_id', $user_cart_id);
        }

        return $current_cart;
    }

	/**
	 * Рассылка уведомлений о неоконченной покупке
	 *
	 * @param Cart $cart
	 */
	public function send_reminders(Cart $cart){
		$reminders = $cart->get_reminders();

		foreach ($reminders as $reminder){
			$user = $reminder->user;

			if(!empty($user->email)){
				Mail::send('emails.cart_reminder', ['user' => $user, 'products' => $reminder->get_products()], function($msg) use ($user){
					$msg->from('info@'.str_replace(['http://', 'https://'], '',  env('APP_URL')), trans('locale.cart.sender_name', ['app_name' => env('APP_NAME')]));
					$msg->to($user->email);
					$msg->subject(trans('locale.cart.unfinished_order_subject', ['app_name' => env('APP_NAME')]));
				});

				$reminder->reminder_success();
			}else{
				$reminder->reminder_success();
			}
		}
	}

	/**
	 * Скрытие уведомления о неоконченной покупке
	 *
	 * @param Cart $cart
	 */
	public function hide_reminder(Cart $cart){
		$current_cart = $cart->current_cart();

		$user_data = json_decode($current_cart->user_data, true);
		if(!is_array($user_data)){
			$user_data = ['statuses' => []];
		}

		$user_data['statuses'][] = 'reminder_showed';

		$current_cart->update(['user_data' => json_encode($user_data)]);
	}
}
