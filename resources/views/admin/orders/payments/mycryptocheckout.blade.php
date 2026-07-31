@include('admin.layouts.form.field-group', [
   'type' => 'select',
   'label' => trans('locale.Currency'),
   'field' => [
    'key' => 'mcc_crypto_currency',
    'options' => $currencies,
    'selected' => [old('mcc_crypto_currency', $order->mсc_crypto_currency)]
   ]
])
<ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between border-0 pb-0">
        <span class="invoice-subtotal-title">MyCryptoCheckout payment ID</span>
        <h6 class="invoice-subtotal-value mb-0">{{ $order->mcc_payment_id }}</h6>
    </li>
    <li class="list-group-item d-flex justify-content-between border-0 pb-0">
        <span class="invoice-subtotal-title">Received</span>
        <h6 class="invoice-subtotal-value mb-0">{{ rtrim(rtrim($order->mсc_crypto_amount, 0), '.') }} {{ $order->mсc_crypto_currency  }}</h6>
    </li>
    <li class="list-group-item d-flex justify-content-between border-0 pb-0">
        <span class="invoice-subtotal-title">To wallet</span>
        <h6 class="invoice-subtotal-value mb-0">{{ $order->mcc_payment_address }}</h6>
    </li>
    <li class="list-group-item d-flex justify-content-between border-0 pb-0">
        <span class="invoice-subtotal-title">MyCryptoCheckout payment status</span>
        <h6 class="invoice-subtotal-value mb-0"><i class="bx {{ $order->mсc_status ? 'bx-checkbox-checked' : 'bx-checkbox' }}"></i></h6>
    </li>
</ul>
