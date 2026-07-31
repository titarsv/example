<form action="/admin/orders/edit/{!! $order->id !!}" method="post" id="edit_form" data-order-id="{{ $order->id }}">
    {!! csrf_field() !!}
    <div class="panel-group">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>{{ trans('locale.Order status') }}</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="row">
                        @if($user->hasAccess(['orders.update']))
                            <div class="form-element col-sm-4">
                                <select name="status" class="form-control">
                                    @foreach($orders_statuses as $status)
                                        @if($status->id == $order->status_id)
                                            <option value="{{ $status->id }}" selected>{{ $status->status }}</option>
                                        @else
                                            <option value="{{ $status->id }}">{{ $status->status }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-element col-sm-8 text-right">
                                <button type="submit" class="btn btn-primary">{{ trans('locale.Save') }}</button>
                                <a href="/admin/orders" class="btn btn-info">{{ trans('locale.Back') }}</a>
                            </div>
                        @else
                            <div class="form-element col-sm-4">
                                <select name="status" class="form-control">
                                    @foreach($orders_statuses as $status)
                                        @if($status->id == $order->status_id)
                                            <option value="{{ $status->id }}" selected>{{ $status->status }}</option>
                                        @else
                                            <option value="{{ $status->id }}">{{ $status->status }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="table table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr class="success">
                        <td align="center">{{ trans('locale.SKU') }}</td>
                        <td>{{ trans('locale.Image') }}</td>
                        <td>{{ trans('locale.Title') }}</td>
                        <td>{{ trans('locale.Availability') }}</td>
                        <td>{{ trans('locale.Quantity') }}</td>
                        <td align="center">{{ trans('locale.Amount') }}</td>
                        <td align="center">{{ trans('locale.Action') }}</td>
                    </tr>
                    </thead>
                    <tbody id="products_table">
                    @foreach($order->getProducts() as $key => $item)
                        <tr>
                            @if(!empty($item['product']))
                            <td align="center">
                                <input type="hidden" name="products[{{ $key }}][code]" value="{{ $item['product_code'] }}">
                                {!! $item['product']->sku !!}
                            </td>
                            <td>
                                @if(!empty($item['product']->image))
                                <img src="{!! $item['product']->image->url() !!}" class="img-thumbnail">
                                @endif
                                <div>{{ $item['price'] }} {{ Helper::getDefaultCurrencySymbol() }}</div>
                            </td>
                            <td>
                                {!! $item['product']->name !!}
                                @if(!empty($item['variations']))
                                    (
                                    @foreach($item['variations'] as $name => $val)
                                        {{ $name }}: {{ $val }};
                                    @endforeach
                                    )
                                @endif
                            </td>
                            <td>
                                @if($item['product']->stock == -2)
                                    {{ trans('locale.Not available') }}
                                @elseif($item['product']->stock == -1)
                                    {{ trans('locale.To order') }}
                                @elseif($item['product']->stock == 0)
                                    {{ trans('locale.Expected') }}
                                @elseif($item['product']->stock > 0)
                                    {{ trans('locale.In stock') }} {{ $item['product']->stock }} {{ trans('locale.pcs') }}.
                                @endif
                            </td>
                            @else
                                <td align="center"></td>
                                <td></td>
                                <td>{{ trans('locale.Product was deleted from site') }}</td>
                            @endif
                            <td>
                                <div class="input-group" style="max-width: 120px;">
                                    <input type="number" class="form-control count_field" step="1" min="1" value="{!! $item['quantity'] !!}" size="5" name="products[{{ $key }}][qty]" data-id="{{ $item['product_code'] }}">
                                    <span class="input-group-addon">{{ trans('locale.pcs') }}</span>
                                </div>
                            </td>
                            <td align="center">{{ round($item['product_sum'], 2) }} {{ Helper::getDefaultCurrencySymbol() }}</td>
                            <td align="center">
                                @if($user->hasAccess(['orders.update']))
                                <button type="button" class="btn btn-primary update_order_product" data-order-id="{{ $order->id }}" data-key="{{ $item['product_code'] }}">
                                    <i class="glyphicon glyphicon-pencil"></i>
                                </button>
                                <a class="btn btn-primary" target="_blank" href="/admin/products/edit/{{ $item['product']->id }}">
                                    <i class="glyphicon glyphicon-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger remove-product-from-order" data-order-id="{{ $order->id }}" data-key="{{ $item['product_code'] }}">
                                    <i class="glyphicon glyphicon-trash"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3">
                            @if($user->hasAccess(['orders.update']))
                            <button type="button" class="btn btn-primary" id="add_to_order">{{ trans('locale.Add product to order') }}</button>
                            @endif
                        </td>
                        <td>{{ !empty($order->total_sale) ? trans('locale.Discount').': '.$order->total_sale.' '.Helper::getDefaultCurrencySymbol() : '' }}</td>
                        <td class="right">{{ trans('locale.Total') }}:</td>
                        <td>{!! $order->total_quantity !!} {{ trans('locale.pcs') }}</td>
                        <td align="center">{!! round($order->total_price - $order->total_sale, 2) !!} {{ Helper::getDefaultCurrencySymbol() }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>{{ trans('locale.Order information') }}</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="table table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <td colspan="2" class="colspan">
                                        Покупатель
                                    </td>
                                </tr>
                                </thead>
                                <tr>
                                    <td>Покупатель</td>
                                    <td>
                                        <input  class="form-control" type="text" name="user_name" value="{{ isset($order->user->name) ? $order->user->name : '' }}">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Телефон</td>
                                    <td>
                                        <input  class="form-control" type="text" name="user_phone" value="{{ isset($order->user->phone) ? $order->user->phone : '' }}">
                                        <p>{!! !empty($order->user->is_callback_off) ? '<br>Не перезванивайте мне, я уверен в заказе' : '' !!}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Почта</td>
                                    <td>
                                        <input  class="form-control" type="email" name="user_email" value="{{ isset($order->user->email) && strpos($order->user->email, '@placeholder.com') === false ? $order->user->email : '' }}">
                                    </td>
                                </tr>
                                <tr>
                                    <td>Дата заказа</td>
                                    <td>{{ $order->created_at->timezone('Europe/Kiev')->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td>Комментарий к заказу</td>
                                    <td>
                                        <textarea class="form-control" name="comment" cols="30" rows="5">{{ !empty($order->user->comment) ? $order->user->comment : '' }}</textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        @if(!empty($delivery_info))
                        <div class="table table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <td colspan="2" class="colspan">
                                        Доставка и оплата
                                    </td>
                                </tr>
                                </thead>
                                @foreach($delivery_info as $key => $value)
                                    @if($key == 'method')
                                        <tr>
                                            <td>Способ доставки</td>
                                            <td>
                                                <select id="js_delivery_method" data-order-id="{{ $order->id }}" class="form-control" name="delivery" autocomplete="off">
                                                    <option value=""></option>
                                                    <option value="pickup"{{ $value == 'Самовывоз' ? ' selected' : '' }}>Самовывоз</option>
                                                    <option value="newpost"{{ $value == 'Новая почта в отделение' ? ' selected' : '' }}>Новая Почта в отделение</option>
                                                    <option value="newpost_courier"{{ $value == 'Новая почта курьером' ? ' selected' : '' }}>Новая почта курьером</option>
                                                    <option value="justin"{{ $value == 'Самовывоз из "Justin"' ? ' selected' : '' }}>Justin</option>
                                                    <option value="courier"{{ $value == 'Курьер по вашему адресу' ? ' selected' : '' }}>Адресная доставка в г. Северодонецк</option>
                                                    <option value="other"{{ !empty($value) && !in_array($value, ['Самовывоз', 'Новая почта в отделение', 'Новая почта курьером', 'Самовывоз из "Justin"', 'Курьер по вашему адресу']) ? ' selected' : '' }}>Другая служба доставки</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endif
                                    @if($key == 'region')
                                        <tr class="delivery">
                                            <td>Область</td>
                                            <td>
                                                @if(is_array($value))
                                                    <select name="region" id="region" class="form-control" onchange="window.{{ in_array($delivery_info['method'], ['Новая почта в отделение', 'Новая почта курьером']) ? 'newpost' : 'justin' }}Update('region', jQuery(this).val())">
                                                        @foreach($value['options'] as $option)
                                                            <option value="{{ $option->id }}"{{ $option->id == $value['selected'] ? ' selected' : '' }}>{{ $option->name_ru }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input name="region" class="form-control" value="{{ $value }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($key == 'city')
                                        <tr class="delivery">
                                            <td>Город</td>
                                            <td>
                                                @if(is_array($value))
                                                    <select name="city" id="city" class="form-control" onchange="window.{{ in_array($delivery_info['method'], ['Новая почта в отделение', 'Новая почта курьером']) ? 'newpost' : 'justin' }}Update('city', jQuery(this).val())">
                                                        @foreach($value['options'] as $option)
                                                            <option value="{{ $option->id }}"{{ $option->id == $value['selected'] ? ' selected' : '' }}>{{ $option->name_ru }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input name="region" class="form-control" value="{{ $value }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($key == 'warehouse')
                                        <tr class="delivery">
                                            <td>Отделение почтовой службы</td>
                                            <td>
                                                @if(is_array($value))
                                                    <select name="warehouse" id="warehouse" class="form-control">
                                                        @foreach($value['options'] as $option)
                                                            <option value="{{ $option->id }}"{{ $option->id == $value['selected'] ? ' selected' : '' }}>{{ $option->address_ru }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input name="region" class="form-control" value="{{ $value }}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    @if($key == 'index' || $key == 'post_code')
                                        <tr class="delivery">
                                            <td>Почтовый индекс</td>
                                            <td><input name="post_code" class="form-control" value="{{ $value }}"></td>
                                        </tr>
                                    @endif
                                    @if($key == 'street')
                                        <tr class="delivery">
                                            <td>Улица</td>
                                            <td><input name="street" class="form-control" value="{{ $value }}"></td>
                                        </tr>
                                    @endif
                                    @if($key == 'house')
                                        <tr class="delivery">
                                            <td>Дом</td>
                                            <td><input name="house" class="form-control" value="{{ $value }}"></td>
                                        </tr>
                                    @endif
                                    @if($key == 'apartment')
                                        <tr class="delivery">
                                            <td>Квартира</td>
                                            <td><input name="apartment" class="form-control" value="{{ $value }}"></td>
                                        </tr>
                                    @endif
                                    @if($key == 'error')
                                        <tr class="delivery">
                                            <td colspan="2" class="colspan">{!! $value !!}</td>
                                        </tr>
                                    @endif
                                    @if($key == 'ttn')
                                        <tr class="delivery">
                                            <td>Экспресс-накладная</td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="js_ttn" value="{{ !empty($value) ? $value : '' }}" placeholder="Ввести вручную" autocomplete="off">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-primary" id="js_save_ttn" type="button"><i class="glyphicon glyphicon-refresh"></i></button>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                @if(isset($delivery_info['method']) && in_array($delivery_info['method'], ['Новая почта в отделение', 'Новая почта курьером', 'Самовывоз из "Justin"']) && empty($delivery_info['ttn']))
                                    <tr class="delivery">
                                        <td>Номер экспресс-накладной</td>
                                        <td>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="js_ttn" value="{{ !empty($ttn) ? $ttn : '' }}" placeholder="Ввести вручную" autocomplete="off">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary" id="js_save_ttn" type="button"><i class="glyphicon glyphicon-refresh"></i></button>
                                                </span>
                                            </div>
                                            @if((in_array($delivery_info['method'], ['Новая почта в отделение', 'Самовывоз из "Justin"']) && !empty($delivery_info['warehouse']['selected']))
                                             || ($delivery_info['method'] == 'Новая почта курьером' && !empty($delivery_info['city']['selected']) && !empty($delivery_info['street']) && !empty($delivery_info['house'])))
                                                <span class="or"><span>или</span></span>
                                                <button type="button" id="js_generate_np_ttn" class="btn btn-success">Сгенерировать ЭН</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>Способ оплаты</td>
                                    <td>
                                        <select class="form-control" name="payment" autocomplete="off">
                                            <option value=""></option>
                                            <option value="cash"{{ $order->payment == 'cash' ? ' selected' : '' }}>Предоплата от 50 грн на карту (Остаток на наложенный платеж)</option>
                                            <option value="online"{{ $order->payment == 'online' ? ' selected' : '' }}>Оплата онлайн</option>
                                            <option value="card"{{ $order->payment == 'card' ? ' selected' : '' }}>Оплата на карту</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        @else
                            <div class="table table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <td colspan="2" class="colspan">
                                            Доставка и оплата
                                        </td>
                                    </tr>
                                    </thead>
                                    <tr>
                                        <td>Способ доставки</td>
                                        <td>
                                            <select id="js_delivery_method" data-order-id="{{ $order->id }}" class="form-control" name="delivery" autocomplete="off">
                                                <option value=""></option>
                                                <option value="pickup" selected>Самовывоз</option>
                                                <option value="newpost">Новая Почта</option>
                                                <option value="justin">Justin</option>
                                                <option value="courier">Адресная доставка в г. Северодонецк</option>
                                                <option value="other">Другая служба доставки</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Способ оплаты</td>
                                        <td>
                                            <select class="form-control" name="payment" autocomplete="off">
                                                <option value=""></option>
                                                <option value="cash">Предоплата от 50 грн на карту (Остаток на наложенный платеж)</option>
                                                <option value="online">Оплата онлайн</option>
                                                <option value="card">Оплата на карту</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>Настройки</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-2 text-right">Примечания</label>
                        <div class="form-element col-sm-10">
                            <textarea name="notes" class="form-control" rows="6">{!! $order->notes !!}</textarea>
                        </div>
                    </div>
                </div>
                @if($user->hasAccess(['orders.update']))
                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-10 col-sm-push-2 text-left">
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                            <a href="/admin/orders" class="btn btn-info">Назад</a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>История</h4>
            </div>
            <div class="panel-body">
                <div class="form-group" style="margin-bottom: 0;">
                    <div class="row">
                        <label class="col-sm-2 text-right">{{ $order->created_at->timezone('Europe/Kiev')->format('Y-m-d H:i:s') }}</label>
                        <div class="form-element col-sm-10">
                            <p style="margin-top: 6px;">Создание заказа</p>
                        </div>
                    </div>
                </div>
                @foreach($order->history as $time => $history)
                    @php
                        $date = new \Carbon\Carbon($time);
                    @endphp
                    <div class="form-group" style="margin-bottom: 0;">
                        <div class="row">
                            <label class="col-sm-2 text-right">{{ $date->timezone('Europe/Kiev')->format('Y-m-d H:i:s') }}</label>
                            <div class="form-element col-sm-10">
                                <p style="margin-top: 6px;">{{ $history['msg'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</form>
