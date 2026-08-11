<div class="card">
    <div class="card-body">
        <span class="badge text-bg-success mb-2">{{ $order->status ? $order->status->status : trans('locale.Unfinished') }}</span>
        <p>
            Заказ <strong>№{{ $order->id }}</strong> оформлен <strong>{{ $order->created_at->format('d.m.Y') }}</strong>,
            текущий статус: <strong>{{ $order->status ? $order->status->status : trans('locale.Unfinished') }}</strong>.
        </p>

        @if(!empty($delivery_info['tracking']))
            <h2 class="h6 mt-4">Информация о доставке</h2>
            @foreach($delivery_info['tracking'] as $tracking)
                <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                    <div>
                        <div class="fw-semibold">{{ $tracking['tracking_number'] }}</div>
                        <div class="small text-muted">Отправлено: {{ date('d.m.Y', strtotime($tracking['shipped_date'])) }}</div>
                    </div>
                </div>
            @endforeach
        @endif

        <h2 class="h6 mt-4">Детали заказа</h2>
        <table class="table table-sm">
            <tbody>
            <tr>
                <th class="fw-normal text-muted">Товары</th>
                <td>
                    @foreach($order->getProducts() as $i => $product)
                        {{ $product['product']->name }}{{ $product['quantity'] > 1 ? ' x' . $product['quantity'] : '' }}{{ $i < count($order->getProducts()) - 1 ? ', ' : '' }}
                    @endforeach
                </td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Сумма</th>
                <td>₽{{ $order->total_price }}</td>
            </tr>
            @if($order->total_sale > 0)
                <tr>
                    <th class="fw-normal text-muted">Скидка</th>
                    <td>-₽{{ $order->total_sale }}</td>
                </tr>
            @endif
            <tr>
                <th class="fw-normal text-muted">Доставка</th>
                <td>{{ !empty($order->delivery_cost) ? '₽'.$order->delivery_cost : 'Бесплатно' }}</td>
            </tr>
            <tr>
                <th class="fw-normal text-muted">Оплата</th>
                <td>{{ $order->payment_name }}</td>
            </tr>
            <tr class="fw-bold">
                <th class="fw-normal text-muted">Итого</th>
                <td>₽{{ $order->full_price }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
