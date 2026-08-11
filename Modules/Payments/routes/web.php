<?php

/*
|--------------------------------------------------------------------------
| No routes of its own - Ipay/Liqpay/WayForPay are called directly from
| OrdersController/CheckoutController, which stay in core (tightly coupled
| to Cart/Checkout/Orders, already gated behind module_active('cart_checkout')).
| This file exists for scaffold consistency with the other Modules/*.
|--------------------------------------------------------------------------
*/