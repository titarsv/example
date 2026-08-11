<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Cartalyst\Sentinel\Native\Facades\Sentinel;

class AccountController extends Controller
{
    /**
     * Личный кабинет: краткая сводка + последние заказы. Route is gated by
     * the existing 'user' middleware (App\Http\Middleware\UserMiddleware),
     * so Sentinel::check() is always a logged-in user here.
     */
    public function indexAction()
    {
        $user = Sentinel::check();

        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('public.account.index')
            ->with('user', $user)
            ->with('orders', $orders);
    }

    /**
     * Полная история заказов пользователя.
     */
    public function ordersAction()
    {
        $user = Sentinel::check();

        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('public.account.orders')
            ->with('user', $user)
            ->with('orders', $orders);
    }

    /**
     * Детали одного заказа — доступны только его владельцу.
     */
    public function orderAction($id)
    {
        $user = Sentinel::check();

        $order = Order::where('id', $id)->where('user_id', $user->id)->first();

        if (empty($order)) {
            abort(404);
        }

        return view('public.account.order')
            ->with('user', $user)
            ->with('order', $order)
            ->with('user_info', $order->getUserInfo());
    }
}
