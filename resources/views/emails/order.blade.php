<div class="header" style="text-align: center;">
    <img src="{!! url('/images/logo.png') !!}" alt="logo" title="{{ env('APP_NAME') }}" width="126" height="80" />
    <p style="font-size: 20px;">New order № {{ $order->id }} on the website {{ env('APP_NAME') }}!</p>
</div>

<table border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse" width="100%">
    <tbody>
        <tr style="background:#1185c2; color: #fff; text-transform:uppercase;">
            <td align="center" height="40px" width="20%">Product image</td>
            <td align="center" height="40px" width="40%">Product name</td>
            <td align="center" height="40px" width="20%">Quantity</td>
            <td align="center" height="40px" width="20%">Price</td>
        </tr>
            @foreach($order->getProducts() as $item)
                <tr>
                    <td align="center" width="20%" height="150px">
                        <a href="{!! $item['product']->link() !!}">
                            <img src="{!!!empty($item['product']->image) ? url($item['product']->image->url([100, 100])) : url('/uploads/no_image.jpg') !!}" alt="product-image" width="100px" height="100px" style="object-fit: contain;" title="{!! $item['product']->name !!}">
                        </a>
                    </td>
                    <td align="center" width="40%" height="150px">
                        <a href="{!! $item['product']->link() !!}" style="color: #333;" onmouseover="this.style.color='#333'">
                            {!! $item['product']->name !!}
                            @if(!empty($item['variations']))
                                (
                                @foreach($item['variations'] as $name => $val)
                                    {{ $name }}: {{ $val }};
                                @endforeach
                                )
                            @endif
                        </a>
                    </td>
                    <td align="center" width="20%" height="150px">
                        {!! $item['quantity'] !!}
                    </td>
                    <td align="center" width="20%" height="150px">
                        £{!! $item['product']->price * $item['quantity'] !!}
                    </td>
                </tr>
            @endforeach
        <tr>
            <td colspan="4" height="30px" align="right"><p style="font-size:16px;"><strong>Quantity:</strong> {!! $order->total_quantity !!}</p></td>
        </tr>
        <tr>
            <td colspan="4" height="30px" align="right"><p style="font-size:16px;"><strong>Subtotal:</strong> {{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->total_price) }}</p></td>
        </tr>
        <tr>
            @if($order->total_sale)
                <td colspan="4" height="30px" align="right"><p style="font-size:16px;"><strong>Discount:</strong> - {{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->total_sale) }}</p></td>
            @endif
        </tr>
        <tr>
            <td colspan="4" height="30px" align="right"><p style="font-size:16px;"><strong>Delivery:</strong> {{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->delivery_cost) }}</p></td>
        </tr>
        <tr>
            <td colspan="4" height="30px" align="right"><p style="font-size:16px;"><strong>Total:</strong> {{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->full_price) }}</p></td>
        </tr>
    </tbody>
</table>

@if($admin)
    <p><strong>Customer:</strong> {!! $user['name'] !!}</p>
    @if(strpos($user['email'], '@placeholder.com') === false)
        <p><strong>E-mail:</strong> {!! $user['email'] !!}</p>
    @endif
    @if(!empty($user['comment']))
    <p><strong>Comment on the order:</strong> {!! $user['comment'] !!}</p>
    @endif
@else
    @if($order->payment == 'btcpay')
        <p style="font-size: 16px; color: #333;">Hi {!! $user['name'] !!},<br>
            Thanks for your order. It’s on-hold until we confirm that payment has been received.<br>
            If you have any questions or need assistance, feel free to reach out to us on Telegram <a href="https://t.me/Proper_Support420">@Proper_Support420</a></p>
    @else
        <p style="font-size: 16px; color: #333;">Hi {!! $user['name'] !!},<br>
            Thanks for your order. It’s on-hold until we confirm that payment has been received.<br>
            Please feel free to contact our support on Telegram <a href="https://t.me/Proper_Support420">@Proper_Support420</a> to proceed with your order.</p>
    @endif
@endif

<p style="font-size:16px; color: #333;">Shipping Information:</p>

@foreach($order->getDeliveryInfo() as $key => $value)
    @if($key == 'region') <p><strong>Region: </strong>{!! $value !!}</p> @endif
    @if($key == 'city') <p><strong>City: </strong>{!! $value !!}</p> @endif
    @if($key == 'warehouse') <p><strong>Department: </strong>{!! $value !!}</p> @endif
    @if($key == 'index' || $key == 'post_code') <p><strong>Postal code: </strong>{!! $value !!}</p> @endif
    @if($key == 'street') <p><strong>Street: </strong>{!! $value !!}</p> @endif
    @if($key == 'house') <p><strong>House: </strong>{!! $value !!}</p> @endif
    @if($key == 'apartment') <p><strong>Apartment: </strong>{!! $value !!}</p> @endif
    @if($key == 'error') <p><strong>{!! $value !!}</strong></p> @endif
@endforeach

@if($order->payment == 'mycryptocheckout')
    <p style="font-size:16px; color: #333;"><strong>Payment: </strong>Cryptocurrency</p>
@elseif($order->payment == 'btcpay')
    <p style="font-size:16px; color: #333;"><strong>Payment: </strong>BTCPay</p>
@elseif($order->payment == 'card')
    <p style="font-size:16px; color: #333;"><strong>Payment: </strong>Pay by Bank Card Via a Third-Party Service</p>
@elseif($order->payment == 'properloudpay')
    <p style="font-size:16px; color: #333;"><strong>Payment: </strong>ProperLoudPay</p>
@elseif($order->payment == 'revolut')
    <p style="font-size:16px; color: #333;"><strong>Payment: </strong>Revolut</p>
@endif
