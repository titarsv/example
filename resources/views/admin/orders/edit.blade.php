@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.View order').' № '.$order->id)
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/orders.css')}}">
@endsection

@section('content')
    <!-- order start -->
    <section class="users-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="information-tab" data-toggle="tab"
                               href="#information" aria-controls="information" role="tab" aria-selected="false">
                                <i class="bx bx-info-circle mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Information') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="products-tab" data-toggle="tab"
                               href="#products" aria-controls="products" role="tab" aria-selected="false">
                                <i class="bx bx-store mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Products') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="history-tab" data-toggle="tab"
                               href="#history" aria-controls="history" role="tab" aria-selected="false">
                                <i class="bx bx-history mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.History') }}</span>
                            </a>
                        </li>
                        <li class="custom-control select">
                            <p class="mb-0 mr-1">{{ trans('locale.Status') }}:</p>
                            <select name="status_id" id="status_id" autocomplete="off" class="form-control form-control-sm" form="info_form">
                                @foreach($orders_statuses as $option)
                                    <option value="{{ $option->value }}"
                                        @if(!empty(old('status_id')))
                                            {{ in_array($option->value, (array)old('status_id')) ? ' selected' : '' }}
                                        @elseif(in_array($option->value, [$order->status_id]))
                                            selected
                                        @endif
                                    >{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- order Info form start -->
                            <form action="/admin/orders/edit/{{ $order->id }}" method="post" class="js_ajax_form" id="info_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-xl-4 col-md-12">
                                        <span class="invoice-number mr-50">{{ trans('locale.Order') }} №</span>
                                        <span>{{ $order->id }}</span>
                                    </div>
                                    <div class="col-xl-8 col-md-12">
                                        <div class="d-flex align-items-center justify-content-xl-end flex-wrap">
                                            <div>
                                                <small class="text-muted">{{ trans('locale.Order created') }}:</small>
                                                <span>{{ $order->created_at->format('d.m.Y H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">{{ trans('locale.Customer') }}</div>
                                        </div>
                                        <div class="field-group">
                                            <label>Name</label>
                                            <div class="form-group">
                                                <input type="text" class="form-control form-control-sm" name="name" value="{{ isset($order->user->name) ? $order->user->name : '' }}" autocomplete="off" data-validation-required-message="{{ trans('locale.Fill this field') }}" required="" aria-invalid="false">
                                                <div class="help-block"></div>
                                            </div>
                                        </div>
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'string',
                                            'label' => trans('locale.Email'),
                                            'field' => [
                                             'key' => 'email',
                                             'item' => $order,
                                             'required' => true
                                            ]
                                        ])
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'text',
                                            'label' => trans('locale.Order comment'),
                                            'field' => [
                                             'key' => 'comment',
                                             'item' => $order
                                            ]
                                        ])
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        @if(!empty($delivery_info))
                                            <div class="divider divider-dashed">
                                                <div class="divider-text">{{ trans('locale.Delivery') }}</div>
                                            </div>
                                            @foreach($delivery_info as $key => $value)
                                                @if($key == 'city')
                                                    @if(is_array($value))
                                                        <div class="field-group">
                                                            <label>{{ trans('locale.city') }}</label>
                                                            <div class="form-group validate">
                                                                <select name="city" id="city" autocomplete="off" class="form-control form-control-sm" aria-invalid="false" onchange="window.{{ in_array($delivery_info['method'], ['newpost', 'newpost_courier']) ? 'newpost' : 'justin' }}Update('city', jQuery(this).val())">
                                                                    @foreach($value['options'] as $option)
                                                                        <option value="{{ $option->id }}"{{ $option->id == $value['selected'] ? ' selected' : '' }}>{{ $option->name_ru }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <div class="help-block"></div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="field-group">
                                                            <label>{{ trans('locale.city') }}</label>
                                                            <div class="form-group validate">
                                                                <input type="text" class="form-control form-control-sm" name="city" value="{{ $value }}" autocomplete="off" data-validation-required-message="{{ trans('locale.This field is required') }}" required="" aria-invalid="false">
                                                                <div class="help-block"></div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endif
                                                @if($key == 'index')
                                                    <div class="field-group">
                                                        <label>{{ trans('locale.Postal code') }}</label>
                                                        <div class="form-group validate">
                                                            <input type="text" class="form-control form-control-sm" name="post_code" value="{{ $value }}" autocomplete="off" data-validation-required-message="{{ trans('locale.This field is required') }}" required="" aria-invalid="false">
                                                            <div class="help-block"></div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($key == 'street')
                                                    <div class="field-group">
                                                        <label>{{ trans('locale.Street') }}</label>
                                                        <div class="form-group validate">
                                                            <input type="text" class="form-control form-control-sm" name="street" value="{{ $value }}" autocomplete="off" data-validation-required-message="{{ trans('locale.This field is required') }}" required="" aria-invalid="false">
                                                            <div class="help-block"></div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="divider divider-dashed">
                                                <div class="divider-text">{{ trans('locale.Delivery') }}</div>
                                            </div>
                                            <div class="field-group">
                                                <label>{{ trans('locale.Delivery method') }}</label>
                                                <div class="form-group validate">
                                                    <select name="delivery" id="delivery" class="form-control form-control-sm" aria-invalid="false">
                                                        @foreach($delivery_methods as $key => $method)
                                                            <option value="{{ $key }}"{{ $order->delivery == $key ? ' selected' : '' }}>{{ $method }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="help-block"></div>
                                                </div>
                                            </div>
                                            <div class="field-group">
                                                <label>{{ trans('locale.Address') }}</label>
                                                <div class="form-group validate">
                                                    <input type="text" class="form-control form-control-sm" name="address" value="{{ $order->address }}" autocomplete="off" data-validation-required-message="{{ trans('locale.This field is required') }}" required="" aria-invalid="false">
                                                    <div class="help-block"></div>
                                                </div>
                                            </div>
                                        @endif
                                        <div id="js_tracking_wrapper">
                                            @if(!empty($delivery_info['tracking']))
                                                @foreach($delivery_info['tracking'] as $tracking)
                                                    <div class="alert border-{{ $tracking['mark_shipped'] ? 'success' : 'warning' }} alert-dismissible mb-2" role="alert">
                                                        <span class="d-flex align-items-center justify-content-between mr-1" style="color: #8a99b5 !important; position: absolute; top: 18px; right: 0;">
                                                            @if(!$tracking['mark_shipped'])
                                                                <i class="bx bxs-truck mr-0 cursor-pointer js_tracking_mark_shipped" data-order-id="{{ $order->id }}" data-tracking-number="{{ $tracking['tracking_number'] }}" data-toggle="tooltip" data-placement="top" data-original-title="{{ trans('locale.Mark order as Shipped') }}" ></i>
                                                            @endif
                                                            <i class="bx bx-trash mr-0 cursor-pointer js_tracking_delete" data-order-id="{{ $order->id }}" data-tracking-number="{{ $tracking['tracking_number'] }}" data-toggle="tooltip" data-placement="top" data-original-title="Delete"></i>
                                                        </span>
                                                        <div class="d-flex align-items-center">
                                                            <i class="bx {{ $tracking['mark_shipped'] ? 'bxs-truck' : 'bx-package' }}"></i>
                                                            <span class="d-flex align-items-center justify-content-between" style="width: 100%">
                                                                <span><strong>{{ trans('locale.Tracking number') }}:</strong> <a href="https://www.royalmail.com/track-your-item/?trackNumber={{ $tracking['tracking_number'] }}" target="_blank">{{ $tracking['tracking_number'] }}</a></span>
                                                                <span><strong>{{ trans('locale.Shipped on') }}:</strong> {{ $tracking['shipped_date'] }}</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        @if($me->hasAccess(['orders.write']))
                                            <div class="d-flex flex-sm-row flex-column justify-content-end mt-5">
                                                <button data-id="{{ $order->id }}" type="button" id="js_add_tracking_info" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                    <i class="bx bx-plus-medical"></i>
                                                    <span class="button-text">{{ trans('locale.Add Tracking Info') }}</span>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">Payment</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 col-12">
                                                @include('admin.layouts.form.field-group', [
                                                   'type' => 'select',
                                                   'label' => trans('locale.Payment method'),
                                                   'field' => [
                                                    'key' => 'payment',
                                                    'options' => $payment_methods,
                                                    'selected' => [old('payment', $order->payment)]
                                                   ]
                                                ])
                                                @include('admin.layouts.form.field-group', [
                                                   'type' => 'select',
                                                   'label' => trans('locale.Payment status'),
                                                   'field' => [
                                                    'key' => 'payment_status',
                                                    'options' => [
                                                       (object)['value' => 0, 'name' => 'Waiting for payment'],
                                                       (object)['value' => 1, 'name' => 'Paid'],
                                                    ],
                                                    'selected' => [old('payment_status', $order->payment_status)]
                                                   ]
                                                ])
                                                <div class="js_payment_method_additional_fields">
                                                    @include('admin.orders.payments.'.old('payment', $order->payment))
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between border-0 pb-0">
                                                        <span class="invoice-subtotal-title">Subtotal</span>
                                                        <h6 class="invoice-subtotal-value mb-0">{{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->total_price) }}</h6>
                                                    </li>
                                                    @if($order->total_sale)
                                                        <li class="list-group-item d-flex justify-content-between border-0 pb-0">
                                                            <span class="invoice-subtotal-title">Discount
                                                                @if($order->coupon_sale || $order->crypto_sale || $order->amount_sale )
                                                                    <i class="bx bx-info-circle" style="font-size: 16px;cursor: pointer;" data-toggle="popover" data-html="true" data-placement="right" data-trigger="hover" data-content="
                                                                    {{ $order->coupon_sale ? "Coupon sale: -".sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->coupon_sale): '' }}
                                                                    {{ $order->crypto_sale ? ($order->coupon_sale ? "<br>" : "")."Crypto discount: -".sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->crypto_sale) : '' }}
                                                                    {{ $order->amount_sale ? ($order->coupon_sale || $order->crypto_sale ? "<br>" : "")."Discount on the amount: -".sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->amount_sale) : '' }}
                                                                    "></i>
                                                                @endif
                                                            </span>
                                                            <h6 class="invoice-subtotal-value mb-0">- {{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->total_sale) }}</h6>
                                                        </li>
                                                    @endif
                                                    <li class="list-group-item d-flex justify-content-between border-0 pb-0">
                                                        <span class="invoice-subtotal-title">Delivery</span>
                                                        <h6 class="invoice-subtotal-value mb-0">{{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->delivery_cost) }}</h6>
                                                    </li>
                                                    <li class="list-group-item py-0 border-0 mt-25">
                                                        <hr>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between border-0 py-0">
                                                        <span class="invoice-subtotal-title">Total</span>
                                                        <h6 class="invoice-subtotal-value mb-0">{{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $order->full_price) }}</h6>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['orders.write']))
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-5">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                <span class="button-text">{{ trans('locale.Save changes') }}</span>
                                            </button>
                                            <a href="/admin/orders" class="btn btn-outline-warning glow">{{ trans('locale.Cancel') }}</a>
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- order Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="products" aria-labelledby="products-tab" role="tabpanel">
                            <!-- order products form start -->
                            <form action="/admin/orders/products/{{ $order->id }}" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>{{ trans('locale.Photo') }}</th>
                                                        <th>{{ trans('locale.Product') }}</th>
                                                        <th class="text-center">{{ trans('locale.Price') }}</th>
                                                        <th class="text-center">{{ trans('locale.Quantity') }}</th>
                                                        <th class="text-right">{{ trans('locale.Total') }}</th>
{{--                                                        <th class="text-right">{{ trans('locale.Actions') }}</th>--}}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($order->getProducts() as $product)
                                                        <tr>
                                                            <td><img class="rounded-circle" src="{{ !empty($product['product']->image) ? $product['product']->image->url() : '/images/larchik/no_image.jpg' }}" width="32" height="32"></td>
                                                            <td><a href="/admin/products/edit/{{ $product['product']->id }}" target="_blank">{{ $product['product']->name }}</a></td>
                                                            <td class="text-center">{{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $product['price']) }}</td>
                                                            <td class="text-center">{{ $product['quantity'] }}</td>
                                                            <td class="text-right">{{ sprintf(config('site.modules.shop.currencies.'.config('site.modules.shop.main_currency').'.pricing_template'), $product['price'] * $product['quantity']) }}</td>
{{--                                                            <td class="text-right">--}}
{{--                                                                @if($me->hasAccess(['orders.write']))--}}
{{--                                                                    <a href="#" class="update_order_product" data-order-id="{{ $order->id }}" data-key="{{ $product['product']->id }}" data-toggle="tooltip" title="{{ trans('locale.Edit') }}">--}}
{{--                                                                        <i class="bx bx-edit"></i>--}}
{{--                                                                    </a>--}}
{{--                                                                    <a href="#" class="remove-product-from-order" data-order-id="{{ $order->id }}" data-product-id="{{ $product['product']->id }}" data-toggle="tooltip" title="{{ trans('locale.Remove') }}">--}}
{{--                                                                        <i class="bx bx-trash"></i>--}}
{{--                                                                    </a>--}}
{{--                                                                @endif--}}
{{--                                                            </td>--}}
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
{{--                                    @if($me->hasAccess(['orders.write']))--}}
{{--                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">--}}
{{--                                            <button type="button" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1" id="add_product_btn">--}}
{{--                                                <i class="bx bx-plus"></i> {{ trans('locale.Add product') }}--}}
{{--                                            </button>--}}
{{--                                        </div>--}}
{{--                                    @endif--}}
                                </div>
                            </form>
                            <!-- order products form ends -->
                        </div>
                        <div class="tab-pane fade" id="history" aria-labelledby="history-tab" role="tabpanel">
                            <!-- order history start -->
                            <ul class="widget-timeline ps ps--active-y">
                                @if(!empty($order->history))
                                    @foreach($order->getHistory() as $history)
                                        <li class="timeline-items timeline-icon-success active">
                                            <div class="timeline-time">{{ $history->date }}</div>
                                            <h6 class="timeline-title">{{ $history->status }}</h6>
                                            <div class="timeline-content">
                                                <p>{{ $history->comment }}</p>
                                                @if(!empty($history->data))
                                                    <div class="badge badge-light-primary">{{ json_encode($history->data) }}</div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                            <!-- order history ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- order ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script>
        jQuery(document).ready(function(){
            $('[data-toggle="popover"]').popover();

            // Initialize select2
            $('.select2').select2({
                width: '100%',
                dropdownAutoWidth: true,
                language: {
                    noResults: function() {
                        return '{{ trans('locale.No results found') }}';
                    }
                }
            });

            // Add product modal
            $('#add_product_btn').on('click', function(){
                $('#addProductModal').modal('show');
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Add product to order
            $(document).on('click', '#add_to_order', function(){
                addPlaceholder();
                if(window.xhr && window.xhr.readyState != 4){
                    window.xhr.abort();
                }

                var product_id = $('#product_id').val();
                var quantity = $('#quantity').val();
                var price = $('#price').val();

                if(!product_id || !quantity || !price) {
                    removePlaceholder();
                    toastr.error('{{ trans('locale.Please fill all required fields') }}');
                    return false;
                }

                window.xhr = $.ajax({
                    url: '/admin/orders/{{ $order->id }}/add-product',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_id: product_id,
                        quantity: quantity,
                        price: price
                    },
                    success: function(response) {
                        removePlaceholder();
                        if(response.status == 'success') {
                            toastr.success(response.message);
                            setTimeout(function(){
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        removePlaceholder();
                        var response = xhr.responseJSON;
                        if(response && response.message) {
                            toastr.error(response.message);
                        } else {
                            toastr.error('{{ trans('locale.An error occurred. Please try again.') }}');
                        }
                    }
                });
            });

            // Update order product
            $(document).on('click', '.update_order_product', function(e) {
                e.preventDefault();
                var order_id = $(this).data('order-id');
                var product_id = $(this).data('product-id');

                $.get('/admin/orders/' + order_id + '/product/' + product_id + '/edit', function(response) {
                    if(response.status == 'success') {
                        $('#editProductModal .modal-body').html(response.html);
                        $('#editProductModal').modal('show');
                    } else {
                        toastr.error(response.message);
                    }
                });
            });

            // Remove product from order
            $(document).on('click', '.remove-product-from-order', function(e) {
                e.preventDefault();

                swal({
                    title: '{{ trans('locale.Are you sure?') }}',
                    text: '{{ trans('locale.You will not be able to recover this product!') }}',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ trans('locale.Yes, delete it!') }}',
                    cancelButtonText: '{{ trans('locale.Cancel') }}'
                }).then((result) => {
                    if (result.value) {
                        var order_id = $(this).data('order-id');
                        var product_id = $(this).data('product-id');

                        $.ajax({
                            url: '/admin/orders/' + order_id + '/remove-product',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                product_id: product_id
                            },
                            success: function(response) {
                                if(response.status == 'success') {
                                    toastr.success(response.message);
                                    setTimeout(function(){
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    toastr.error(response.message);
                                }
                            },
                            error: function(xhr) {
                                var response = xhr.responseJSON;
                                if(response && response.message) {
                                    toastr.error(response.message);
                                } else {
                                    toastr.error('{{ trans('locale.An error occurred. Please try again.') }}');
                                }
                            }
                        });
                    }
                });
            });

            // Update order status
            $('.update-order-status').on('click', function() {
                var status = $(this).data('status');

                swal({
                    title: '{{ trans('locale.Change order status') }}',
                    text: '{{ trans('locale.Are you sure you want to change the order status?') }}',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ trans('locale.Yes, change it!') }}',
                    cancelButtonText: '{{ trans('locale.Cancel') }}'
                }).then((result) => {
                    if (result.value) {
                        addPlaceholder();

                        $.ajax({
                            url: '/admin/orders/{{ $order->id }}/update-status',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                status: status
                            },
                            success: function(response) {
                                removePlaceholder();
                                if(response.status == 'success') {
                                    toastr.success(response.message);
                                    setTimeout(function(){
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    toastr.error(response.message);
                                }
                            },
                            error: function(xhr) {
                                removePlaceholder();
                                var response = xhr.responseJSON;
                                if(response && response.message) {
                                    toastr.error(response.message);
                                } else {
                                    toastr.error('{{ trans('locale.An error occurred. Please try again.') }}');
                                }
                            }
                        });
                    }
                });
            });

            // New Post API integration
            window.newpostUpdate = function(id, value) {
                if (id === 'city') {
                    var data = {
                        city_id: value
                    };

                    if (window.deliveryMethod === 'newpost') {
                        data.type = 'warehouse';
                        updateWarehouses(data);
                    } else if (window.deliveryMethod === 'newpost_courier') {
                        data.type = 'street';
                        updateStreets(data);
                    }
                } else if (id === 'warehouse' || id === 'street') {
                    var data = {
                        [id + '_ref']: value
                    };

                    if (id === 'warehouse') {
                        updateWarehouseInfo(data);
                    } else if (id === 'street') {
                        updateStreetInfo(data);
                    }
                }
            };

            function updateWarehouses(data) {
                $.ajax({
                    url: '/api/newpost/warehouses',
                    type: 'GET',
                    data: data,
                    beforeSend: function() {
                        $('select[name="warehouse"]').html('<option value="">{{ trans('locale.Loading...') }}</option>');
                    },
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="">{{ trans('locale.makeChoice') }}</option>';
                            $.each(response.data, function(key, value) {
                                options += '<option value="' + value.ref + '">' + value.description_ru + '</option>';
                            });
                            $('select[name="warehouse"]').html(options);
                        } else {
                            toastr.error(response.message || '{{ trans('locale.An error occurred while loading warehouses') }}');
                        }
                    },
                    error: function() {
                        toastr.error('{{ trans('locale.An error occurred while loading warehouses') }}');
                    }
                });
            }

            function updateStreets(data) {
                $.ajax({
                    url: '/api/newpost/streets',
                    type: 'GET',
                    data: data,
                    beforeSend: function() {
                        $('select[name="street"]').html('<option value="">{{ trans('locale.Loading...') }}</option>');
                    },
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="">{{ trans('locale.makeChoice') }}</option>';
                            $.each(response.data, function(key, value) {
                                options += '<option value="' + value.ref + '">' + value.description_ru + '</option>';
                            });
                            $('select[name="street"]').html(options);
                        } else {
                            toastr.error(response.message || '{{ trans('locale.An error occurred while loading streets') }}');
                        }
                    },
                    error: function() {
                        toastr.error('{{ trans('locale.An error occurred while loading streets') }}');
                    }
                });
            }

            function updateWarehouseInfo(data) {
                $.ajax({
                    url: '/api/newpost/warehouse-info',
                    type: 'GET',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            $('input[name="post_code"]').val(response.data.post_code || '');
                        } else {
                            toastr.error(response.message || '{{ trans('locale.An error occurred while loading warehouse info') }}');
                        }
                    },
                    error: function() {
                        toastr.error('{{ trans('locale.An error occurred while loading warehouse info') }}');
                    }
                });
            }

            function updateStreetInfo(data) {
                $.ajax({
                    url: '/api/newpost/street-info',
                    type: 'GET',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            $('input[name="post_code"]').val(response.data.post_code || '');
                        } else {
                            toastr.error(response.message || '{{ trans('locale.An error occurred while loading street info') }}');
                        }
                    },
                    error: function() {
                        toastr.error('{{ trans('locale.An error occurred while loading street info') }}');
                    }
                });
            }

            // Initialize delivery method
            var deliveryMethod = '{{ $delivery_info['method'] ?? '' }}';
            if (deliveryMethod) {
                window.deliveryMethod = deliveryMethod;
            }

            // Handle delivery method change
            $('select[name="delivery"]').on('change', function() {
                var method = $(this).val();
                window.deliveryMethod = method;

                // Show/hide fields based on delivery method
                if (method === 'newpost') {
                    $('.field-warehouse').show();
                    $('.field-street, .field-house, .field-apartment').hide();
                } else if (method === 'newpost_courier') {
                    $('.field-street, .field-house, .field-apartment').show();
                    $('.field-warehouse').hide();
                } else {
                    $('.field-warehouse, .field-street, .field-house, .field-apartment').hide();
                }
            }).trigger('change');
        });

        // Helper functions
        function addPlaceholder() {
            $('body').append('<div class="block-ui"><div class="block-ui-overlay"></div><div class="block-ui-message-container"><div class="block-ui-message">{{ trans('locale.Loading...') }}</div></div></div>');
        }

        function removePlaceholder() {
            $('.block-ui').remove();
        }

        $('#js_add_tracking_info').click(function(){
            const orderId = $(this).data('id');

            swal({
                title: '{{ trans('locale.Add Tracking Information') }}',
                html: `
                    <form id="trackingForm" class="form">
                        <div class="form-group">
                            <label for="tracking_number" class="form-label">{{ trans('locale.Tracking number') }}:</label>
                            <input type="text" class="form-control" id="tracking_number" name="tracking_number" required>
                        </div>
                        <div class="form-group">
                            <label for="shipped_date" class="form-label">{{ trans('locale.Date shipped') }}:</label>
                            <input type="date" class="form-control" id="shipped_date" name="shipped_date" required>
                        </div>
                        <div class="form-check mt-1">
                          <div class="checkbox checkbox-primary checkbox-icon">
                              <input type="checkbox" class="form-check-input" id="mark_shipped" name="mark_shipped">
                              <label for="mark_shipped"><i class="bx bxs-truck"></i>{{ trans('locale.Mark order as Shipped') }}</label>
                          </div>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: '{{ trans('locale.Save') }}',
                cancelButtonText: '{{ trans('locale.Cancel') }}',
                focusConfirm: false,
                preConfirm: () => {
                    return {
                        action: 'add_tracking',
                        tracking_number: document.getElementById('tracking_number').value,
                        shipped_date: document.getElementById('shipped_date').value,
                        mark_shipped: document.getElementById('mark_shipped').checked,
                        _token: '{{ csrf_token() }}'
                    };
                }
            }).then((result) => {
                if (result.value) {
                    const formData = result.value;

                    $.ajax({
                        url: '/admin/orders/update-tracking/' + orderId,
                        type: 'POST',
                        data: formData,
                        beforeSend: function() {
                            addPlaceholder();
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update the tracking info display
                                $('#js_tracking_wrapper').append(`
                                    <div class="alert border-${formData.mark_shipped ? 'success' : 'warning'} alert-dismissible mb-2" role="alert">
                                        <span class="d-flex align-items-center justify-content-between mr-1" style="color: #8a99b5 !important; position: absolute; top: 18px; right: 0;">
                                            ${formData.mark_shipped ? '' : '<i class="bx bxs-truck mr-0 cursor-pointer js_tracking_mark_shipped" data-order-id="{{ $order->id }}" data-tracking-number="'+formData.tracking_number+'" data-toggle="tooltip" data-placement="top" data-original-title="{{ trans('locale.Mark order as Shipped') }}" ></i>'}
                                            <i class="bx bx-trash mr-0 cursor-pointer js_tracking_delete" data-order-id="{{ $order->id }}" data-tracking-number="${formData.tracking_number}" data-toggle="tooltip" data-placement="top" data-original-title="Delete"></i>
                                        </span>
                                        <div class="d-flex align-items-center">
                                            <i class="bx ${formData.mark_shipped ? 'bxs-truck' : 'bx-package'}"></i>
                                            <span class="d-flex align-items-center justify-content-between" style="width: 100%">
                                                <span><strong>{{ trans('locale.Tracking number') }}:</strong> <a href="https://www.royalmail.com/track-your-item/?trackNumber=${formData.tracking_number}" target="_blank">${formData.tracking_number}</a></span>
                                                <span><strong>{{ trans('locale.Shipped on') }}:</strong> ${formData.shipped_date}</span>
                                            </span>
                                        </div>
                                    </div>
                                `);

                                // Show success message
                                Swal.fire({
                                    icon: 'success',
                                    title: '{{ trans('locale.Success') }}',
                                    text: response.message || '{{ trans('locale.Tracking information updated successfully') }}',
                                    timer: 2000,
                                    showConfirmButton: false
                                });

                                // If order was marked as shipped, reload the page to update status
                                if (formData.mark_shipped) {
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ trans('locale.Error') }}',
                                    text: response.message || '{{ trans('locale.Failed to update tracking information') }}'
                                });
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = '{{ trans('locale.An error occurred while updating tracking information') }}';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ trans('locale.Error') }}',
                                text: errorMessage
                            });
                        },
                        complete: function() {
                            removePlaceholder();
                        }
                    });
                }
            });
        });

        $(document).on('click', '.js_tracking_mark_shipped', function(){
            const $this = $(this);
            const orderId = $this.data('order-id');
            const trackingNumber = $this.data('tracking-number');
            const $alert = $this.parents('.alert');

            // Show loading state
            $this.replaceWith('<i class="bx bx-loader bx-spin" style="margin-right: 0;"></i>');

            $.ajax({
                url: '/admin/orders/update-tracking/' + orderId,
                type: 'POST',
                data: {
                    action: 'mark_shipped',
                    tracking_number: trackingNumber,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Update the UI to show the tracking is now marked as shipped
                        $alert.removeClass('border-warning').addClass('border-success');
                        $alert.find('.bx-package').removeClass('bx-package').addClass('bxs-truck');
                        $alert.find('.bx-spin').remove(); // Remove the action buttons

                        // Show success message
                        toastr.success(response.message || '{{ trans('locale.Tracking marked as shipped') }}');
                    } else {
                        // Show error message
                        toastr.error(response.message || '{{ trans('locale.Failed to update tracking status') }}');
                        // Restore the button
                        $this.replaceWith('<i class="bx bxs-truck mr-0 cursor-pointer js_tracking_mark_shipped" data-order-id="' + orderId + '" data-tracking-number="' + trackingNumber + '" data-toggle="tooltip" data-placement="top" data-original-title="{{ trans('locale.Mark order as Shipped') }}"></i>');
                    }
                },
                error: function(xhr) {
                    let errorMessage = '{{ trans('locale.An error occurred while updating tracking status') }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                    // Restore the button
                    $this.replaceWith('<i class="bx bxs-truck mr-0 cursor-pointer js_tracking_mark_shipped" data-order-id="' + orderId + '" data-tracking-number="' + trackingNumber + '" data-toggle="tooltip" data-placement="top" data-original-title="{{ trans('locale.Mark order as Shipped') }}"></i>');
                }
            });
        });

        $(document).on('click', '.js_tracking_delete', function(e) {
            e.preventDefault();

            const $this = $(this);
            const orderId = $this.data('order-id');
            const trackingNumber = $this.data('tracking-number');
            const $alert = $this.closest('.alert');

            // Show confirmation dialog
            Swal.fire({
                title: '{{ trans('locale.Are you sure?') }}',
                text: '{{ trans('locale.This action cannot be undone') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ trans('locale.Yes, delete it') }}',
                cancelButtonText: '{{ trans('locale.Cancel') }}'
            }).then((result) => {
                if (result.value) {
                    // Show loading state
                    $this.replaceWith('<i class="bx bx-loader bx-spin" style="margin-right: 0;"></i>');

                    $.ajax({
                        url: '/admin/orders/update-tracking/' + orderId,
                        type: 'POST',
                        data: {
                            action: 'delete_tracking',
                            tracking_number: trackingNumber,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Remove the tracking entry from UI
                                $alert.fadeOut(300, function() {
                                    $(this).remove();
                                });

                                // Show success message
                                toastr.success(response.message || '{{ trans('locale.Tracking information deleted successfully') }}');
                            } else {
                                // Show error message
                                toastr.error(response.message || '{{ trans('locale.Failed to delete tracking information') }}');
                                // Restore the button
                                $this.replaceWith('<i class="bx bx-trash mr-0 cursor-pointer js_tracking_delete" data-order-id="' + orderId + '" data-tracking-number="' + trackingNumber + '" data-toggle="tooltip" data-placement="top" data-original-title="{{ trans('locale.Delete') }}"></i>');
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = '{{ trans('locale.An error occurred while deleting tracking information') }}';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            toastr.error(errorMessage);
                            // Restore the button
                            $this.replaceWith('<i class="bx bx-trash mr-0 cursor-pointer js_tracking_delete" data-order-id="' + orderId + '" data-tracking-number="' + trackingNumber + '" data-toggle="tooltip" data-placement="top" data-original-title="{{ trans('locale.Delete') }}"></i>');
                        }
                    });
                }
            });
        });
    </script>
@endsection
