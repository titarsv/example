@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Shop settings'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
@endsection

@section('content')
    <!-- settings edit start -->
    <h1 class="pages-title">{{ trans('locale.Shop settings') }}</h1>
    <section class="users-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
{{--                        <li class="nav-item">--}}
{{--                            <a class="nav-link d-flex align-items-center btn-sm active" id="delivery-tab" data-toggle="tab"--}}
{{--                               href="#delivery" aria-controls="delivery" role="tab" aria-selected="false">--}}
{{--                                <i class="bx bxs-truck mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Delivery') }}</span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="payment-tab" data-toggle="tab"
                               href="#payment" aria-controls="payment" role="tab" aria-selected="false">
                                <i class="bx bx-dollar mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Payment') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="contacts-tab" data-toggle="tab"
                               href="#contacts" aria-controls="contacts" role="tab" aria-selected="false">
                                <i class="bx bx-mail-send mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Contacts') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="trustpilot-tab" data-toggle="tab"
                               href="#trustpilot" aria-controls="trustpilot" role="tab" aria-selected="false">
                                <i class="bx bxs-star mr-25"></i><span class="d-none d-sm-block">Trustpilot</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="maintenance-tab" data-toggle="tab"
                               href="#maintenance" aria-controls="maintenance" role="tab" aria-selected="false">
                                <i class="bx bxs-cog mr-25"></i><span class="d-none d-sm-block">Maintenance</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
{{--                        <div class="tab-pane active fade show" id="delivery" aria-labelledby="delivery-tab" role="tabpanel">--}}
{{--                            <!-- settings edit delivery form start -->--}}
{{--                            <form action="/admin/shop/settings/delivery" method="post" class="js_ajax_form" novalidate>--}}
{{--                                {!! csrf_field() !!}--}}
{{--                                <div class="row">--}}
{{--                                    <div class="col-12">--}}
{{--                                        <div class="divider divider-dashed">--}}
{{--                                            <div class="divider-text">{{ trans('locale.Pickup') }}</div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <fieldset>--}}
{{--                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
{{--                                                        <div class="input-group-prepend">--}}
{{--                                                            <div class="input-group-text" style="padding: 0 5px 8px;">--}}
{{--                                                                <div class="checkbox checkbox-sm">--}}
{{--                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="pickup" id="delivery_methods_pickup" name="delivery_methods[]"{{ !empty($settings->delivery_methods) && in_array('pickup', $settings->delivery_methods) ? ' checked' : '' }}>--}}
{{--                                                                    <label for="delivery_methods_pickup"></label>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="input-group-append">--}}
{{--                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
{{--                                                        </div>--}}
{{--                                                        @foreach($languages as $lang_key => $lang_name)--}}
{{--                                                            <input autocomplete="off"--}}
{{--                                                               type="text"--}}
{{--                                                               class="form-control form-control-sm"--}}
{{--                                                               name="delivery_pickup_name_{{ $lang_key }}"--}}
{{--                                                               id="delivery_pickup_name_{{ $lang_key }}"--}}
{{--                                                               value="{{ !empty($settings->{'delivery_pickup_name_'.$lang_key}) ? $settings->{'delivery_pickup_name_'.$lang_key} : trans('locale.Pickup') }}"--}}
{{--                                                               style="border-radius: 0">--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                </fieldset>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="divider divider-dashed">--}}
{{--                                            <div class="divider-text">{{ trans('locale.Our courier') }}</div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <fieldset>--}}
{{--                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
{{--                                                        <div class="input-group-prepend">--}}
{{--                                                            <div class="input-group-text" style="padding: 0 5px 8px;">--}}
{{--                                                                <div class="checkbox checkbox-sm">--}}
{{--                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="courier" id="delivery_methods_courier" name="delivery_methods[]"{{ !empty($settings->delivery_methods) && in_array('courier', $settings->delivery_methods) ? ' checked' : '' }}>--}}
{{--                                                                    <label for="delivery_methods_courier"></label>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="input-group-append">--}}
{{--                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
{{--                                                        </div>--}}
{{--                                                        @foreach($languages as $lang_key => $lang_name)--}}
{{--                                                            <input autocomplete="off"--}}
{{--                                                               type="text"--}}
{{--                                                               class="form-control form-control-sm"--}}
{{--                                                               name="delivery_courier_name_{{ $lang_key }}"--}}
{{--                                                               id="delivery_courier_name_{{ $lang_key }}"--}}
{{--                                                               value="{{ !empty($settings->{'delivery_courier_name_'.$lang_key}) ? $settings->{'delivery_courier_name_'.$lang_key} : trans('locale.Courier') }}"--}}
{{--                                                               style="border-radius: 0">--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                </fieldset>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="divider divider-dashed">--}}
{{--                                            <div class="divider-text">{{ trans('locale.New Mail') }}</div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <fieldset>--}}
{{--                                                    <label>{{ trans('locale.New Mail branch') }}</label>--}}
{{--                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
{{--                                                        <div class="input-group-prepend">--}}
{{--                                                            <div class="input-group-text" style="padding: 0 5px 8px;">--}}
{{--                                                                <div class="checkbox checkbox-sm">--}}
{{--                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="newpost" id="delivery_methods_newpost" name="delivery_methods[]"{{ !empty($settings->delivery_methods) && in_array('newpost', $settings->delivery_methods) ? ' checked' : '' }}>--}}
{{--                                                                    <label for="delivery_methods_newpost"></label>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="input-group-append">--}}
{{--                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
{{--                                                        </div>--}}
{{--                                                        @foreach($languages as $lang_key => $lang_name)--}}
{{--                                                            <input autocomplete="off"--}}
{{--                                                               type="text"--}}
{{--                                                               class="form-control form-control-sm"--}}
{{--                                                               name="delivery_newpost_name_{{ $lang_key }}"--}}
{{--                                                               id="delivery_newpost_name_{{ $lang_key }}"--}}
{{--                                                               value="{{ !empty($settings->{'delivery_newpost_name_'.$lang_key}) ? $settings->{'delivery_newpost_name_'.$lang_key} : trans('locale.New Mail branch') }}"--}}
{{--                                                               style="border-radius: 0">--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                </fieldset>--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <fieldset>--}}
{{--                                                    <label>{{ trans('locale.Courier of Nova Poshta') }}</label>--}}
{{--                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
{{--                                                        <div class="input-group-prepend">--}}
{{--                                                            <div class="input-group-text" style="padding: 0 5px 8px;">--}}
{{--                                                                <div class="checkbox checkbox-sm">--}}
{{--                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="newpost_courier" id="delivery_methods_newpost_courier" name="delivery_methods[]"{{ !empty($settings->delivery_methods) && in_array('newpost_courier', $settings->delivery_methods) ? ' checked' : '' }}>--}}
{{--                                                                    <label for="delivery_methods_newpost_courier"></label>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="input-group-append">--}}
{{--                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
{{--                                                        </div>--}}
{{--                                                        @foreach($languages as $lang_key => $lang_name)--}}
{{--                                                            <input autocomplete="off"--}}
{{--                                                               type="text"--}}
{{--                                                               class="form-control form-control-sm"--}}
{{--                                                               name="delivery_newpost_courier_name_{{ $lang_key }}"--}}
{{--                                                               id="delivery_newpost_courier_name_{{ $lang_key }}"--}}
{{--                                                               value="{{ !empty($settings->{'delivery_newpost_courier_name_'.$lang_key}) ? $settings->{'delivery_newpost_courier_name_'.$lang_key} : trans('locale.Courier of Nova Poshta') }}"--}}
{{--                                                               style="border-radius: 0">--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                </fieldset>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'string',--}}
{{--                                                    'label' => trans('locale.API Key'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'newpost_api_key',--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'select',--}}
{{--                                                    'label' => trans('locale.The period of renewal of the regions of Ukraine'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'newpost_regions_update_period',--}}
{{--                                                     'options' => [(object)['value' => 0, 'name' => trans('locale.Not selected')], (object)['value' => 86400, 'name' => trans('locale.Every day')], (object)['value' => 604800, 'name' => trans('locale.Once a week')], (object)['value' => 2592000, 'name' => trans('locale.Once a month')], (object)['value' => 15552000, 'name' => trans('locale.Once every six months')]],--}}
{{--                                                     'selected' => [isset($settings->newpost_regions_update_period) ? $settings->newpost_regions_update_period : 0],--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Last update date of regions of Ukraine') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <input type="text" class="form-control form-control-sm" value="{!! !empty($settings->newpost_regions_last_update) ? date('d.m.Y', $settings->newpost_regions_last_update) : trans('locale.No data, needs to be updated!') !!}" autocomplete="off" aria-invalid="false" readonly>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'select',--}}
{{--                                                    'label' => trans('locale.The period of urban renewal in Ukraine'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'newpost_regions_update_period',--}}
{{--                                                     'options' => $update_period,--}}
{{--                                                     'selected' => [isset($settings->newpost_cities_update_period) ? $settings->newpost_cities_update_period : 0],--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Date of last update of cities of Ukraine') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <input type="text" class="form-control form-control-sm" value="{!! !empty($settings->newpost_cities_last_update) ? date('d.m.Y', $settings->newpost_cities_last_update) : trans('locale.No data, needs to be updated!') !!}" autocomplete="off" aria-invalid="false" readonly>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'select',--}}
{{--                                                    'label' => trans('locale.Period of renewal of NP branches'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'newpost_regions_update_period',--}}
{{--                                                     'options' => $update_period,--}}
{{--                                                     'selected' => [isset($settings->newpost_warehouses_update_period) ? $settings->newpost_warehouses_update_period : 0],--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Date of last update of NP branches') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <input type="text" class="form-control form-control-sm" value="{!! !empty($settings->newpost_warehouses_last_update) ? date('d.m.Y', $settings->newpost_warehouses_last_update) : trans('locale.No data, needs to be updated!') !!}" autocomplete="off" aria-invalid="false" readonly>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Sender') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <select name="newpost_sender_id" id="newpost_sender_id" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
{{--                                                            @foreach($np_senders as $sender)--}}
{{--                                                                <option value="{{ $sender['Ref'] }}"{{ !empty($settings->newpost_sender_id) && $sender['Ref'] == $settings->newpost_sender_id }}>{{ $sender['Description'] }}</option>--}}
{{--                                                            @endforeach--}}
{{--                                                        </select>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Dispatch area') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <select name="newpost_sender_region_id" id="newpost_sender_region_id" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
{{--                                                            @foreach($regions as $region)--}}
{{--                                                                <option value="{{ $region->region_id }}"{{ !empty($region_id) && $region->region_id == $region_id ? ' selected' : '' }}>{{ $region->name_ru }}</option>--}}
{{--                                                            @endforeach--}}
{{--                                                        </select>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.City of dispatch') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <select name="newpost_sender_city_id" id="newpost_sender_city_id" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
{{--                                                            @foreach($cities as $city)--}}
{{--                                                                <option value="{{ $city->city_id }}"{{ !empty($city_id) && $city->city_id == $city_id ? ' selected' : '' }}>{{ $city->name_ru }}</option>--}}
{{--                                                            @endforeach--}}
{{--                                                        </select>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Dispatch department') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <select name="newpost_warehouse_sender_id" id="newpost_warehouse_sender_id" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
{{--                                                            @foreach($warehouses as $warehouse)--}}
{{--                                                                <option value="{{ $warehouse->warehouse_id }}"{{ !empty($settings->newpost_warehouse_sender_id) && $warehouse->warehouse_id == $settings->newpost_warehouse_sender_id }}>{{ $warehouse->address_ru }}</option>--}}
{{--                                                            @endforeach--}}
{{--                                                        </select>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'string',--}}
{{--                                                    'label' => trans('locale.Sender\'s phone number'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'newpost_sender_contact_phone',--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="divider divider-dashed">--}}
{{--                                            <div class="divider-text">Justin</div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <fieldset>--}}
{{--                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
{{--                                                        <div class="input-group-prepend">--}}
{{--                                                            <div class="input-group-text" style="padding: 0 5px 8px;">--}}
{{--                                                                <div class="checkbox checkbox-sm">--}}
{{--                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="justin" id="delivery_methods_justin" name="delivery_methods[]"{{ !empty($settings->delivery_methods) && in_array('justin', $settings->delivery_methods) ? ' checked' : '' }}>--}}
{{--                                                                    <label for="delivery_methods_justin"></label>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="input-group-append">--}}
{{--                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
{{--                                                        </div>--}}
{{--                                                        @foreach($languages as $lang_key => $lang_name)--}}
{{--                                                            <input autocomplete="off"--}}
{{--                                                                   type="text"--}}
{{--                                                                   class="form-control form-control-sm"--}}
{{--                                                                   name="delivery_justin_name_{{ $lang_key }}"--}}
{{--                                                                   id="delivery_justin_name_{{ $lang_key }}"--}}
{{--                                                                   value="{{ !empty($settings->{'delivery_justin_name_'.$lang_key}) ? $settings->{'delivery_justin_name_'.$lang_key} : 'Justin' }}"--}}
{{--                                                                   style="border-radius: 0">--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                </fieldset>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'string',--}}
{{--                                                    'label' => trans('locale.Account Key'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'justin_account_key',--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'string',--}}
{{--                                                    'label' => trans('locale.API Key'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'justin_api_key',--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'string',--}}
{{--                                                    'label' => trans('locale.Sender'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'justin_sender_name',--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Dispatch area') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <select name="justin_sender_region_id" id="justin_sender_region_id" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
{{--                                                            @foreach($justin_regions as $rid => $region)--}}
{{--                                                                <option value="{{ $rid }}"{{ !empty($justin_region_id) && $rid == $justin_region_id ? ' selected' : '' }}>{{ $region['name'] }}</option>--}}
{{--                                                            @endforeach--}}
{{--                                                        </select>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.City of dispatch') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <select name="justin_sender_city_id" id="justin_sender_city_id" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
{{--                                                            @foreach($justin_cities as $cid => $city)--}}
{{--                                                                <option value="{{ $cid }}"{{ !empty($justin_city_id) && $cid == $justin_city_id ? ' selected' : '' }}>{{ $city['name'] }}</option>--}}
{{--                                                            @endforeach--}}
{{--                                                        </select>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                <div class="field-group">--}}
{{--                                                    <label>{{ trans('locale.Dispatch department') }}</label>--}}
{{--                                                    <div class="form-group validate">--}}
{{--                                                        <select name="justin_warehouse_sender_id" id="justin_warehouse_sender_id" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
{{--                                                            @foreach($justin_warehouses as $wid => $warehouse)--}}
{{--                                                                <option value="{{ $wid }}"{{ !empty($justin_warehouse_id) && $wid == $justin_warehouse_id  ? ' selected' : '' }}>{{ $warehouse['name'] }}</option>--}}
{{--                                                            @endforeach--}}
{{--                                                        </select>--}}
{{--                                                        <div class="help-block"></div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'string',--}}
{{--                                                    'label' => trans('locale.Sender\'s phone number'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'justin_sender_phone',--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                            <div class="col">--}}
{{--                                                @include('admin.layouts.form.field-group', [--}}
{{--                                                    'type' => 'string',--}}
{{--                                                    'label' => trans('locale.Sender\'s company'),--}}
{{--                                                    'languages' => null,--}}
{{--                                                    'field' => [--}}
{{--                                                     'key' => 'justin_sender_company',--}}
{{--                                                     'item' => $settings--}}
{{--                                                    ]--}}
{{--                                                ])--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <div class="divider divider-dashed">--}}
{{--                                            <div class="divider-text">{{ trans('locale.Other') }}</div>--}}
{{--                                        </div>--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="col">--}}
{{--                                                <fieldset>--}}
{{--                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
{{--                                                        <div class="input-group-prepend">--}}
{{--                                                            <div class="input-group-text" style="padding: 0 5px 8px;">--}}
{{--                                                                <div class="checkbox checkbox-sm">--}}
{{--                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="other" id="delivery_methods_other" name="delivery_methods[]"{{ !empty($settings->delivery_methods) && in_array('other', $settings->delivery_methods) ? ' checked' : '' }}>--}}
{{--                                                                    <label for="delivery_methods_other"></label>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}
{{--                                                        <div class="input-group-append">--}}
{{--                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
{{--                                                        </div>--}}
{{--                                                        @foreach($languages as $lang_key => $lang_name)--}}
{{--                                                            <input autocomplete="off"--}}
{{--                                                                   type="text"--}}
{{--                                                                   class="form-control form-control-sm"--}}
{{--                                                                   name="delivery_other_name_{{ $lang_key }}"--}}
{{--                                                                   id="delivery_other_name_{{ $lang_key }}"--}}
{{--                                                                   value="{{ !empty($settings->{'delivery_other_name_'.$lang_key}) ? $settings->{'delivery_other_name_'.$lang_key} : trans('locale.Other') }}"--}}
{{--                                                                   style="border-radius: 0">--}}
{{--                                                        @endforeach--}}
{{--                                                    </div>--}}
{{--                                                </fieldset>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                    @if($me->hasAccess(['seo.write']))--}}
{{--                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">--}}
{{--                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">--}}
{{--                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>--}}
{{--                                                {{ trans('locale.Save changes') }}--}}
{{--                                            </button>--}}
{{--                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>--}}
{{--                                        </div>--}}
{{--                                    @endif--}}
{{--                                </div>--}}
{{--                            </form>--}}
{{--                            <!-- settings edit delivery form ends -->--}}
{{--                        </div>--}}
                        <div class="tab-pane active fade show" id="payment" aria-labelledby="payment-tab" role="tabpanel">
                            <!-- settings edit payment form start -->
                            <form action="/admin/shop/settings/payment" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">{{ trans('locale.In cash') }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <fieldset>
                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="cash" id="payment_methods_cash" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('cash', $settings->payment_methods) ? ' checked' : '' }}>
                                                                    <label for="payment_methods_cash"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>
                                                        </div>
                                                        @foreach($languages as $lang_key => $lang_name)
                                                            <input autocomplete="off"
                                                                   type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="payment_cash_name_{{ $lang_key }}"
                                                                   id="payment_cash_name_{{ $lang_key }}"
                                                                   value="{{ !empty($settings->{'payment_cash_name_'.$lang_key}) ? $settings->{'payment_cash_name_'.$lang_key} : trans('locale.In cash') }}"
                                                                   style="border-radius: 0">
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">{{ trans('locale.Payment by card') }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <fieldset>
                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="card" id="payment_methods_card" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('card', $settings->payment_methods) ? ' checked' : '' }}>
                                                                    <label for="payment_methods_card"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>
                                                        </div>
                                                        @foreach($languages as $lang_key => $lang_name)
                                                            <input autocomplete="off"
                                                                   type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="payment_card_name_{{ $lang_key }}"
                                                                   id="payment_card_name_{{ $lang_key }}"
                                                                   value="{{ !empty($settings->{'payment_card_name_'.$lang_key}) ? $settings->{'payment_card_name_'.$lang_key} : trans('locale.Payment by card') }}"
                                                                   style="border-radius: 0">
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">{{ trans('locale.Online') }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <fieldset>
                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="online" id="payment_methods_online" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('online', $settings->payment_methods) ? ' checked' : '' }}>
                                                                    <label for="payment_methods_online"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>
                                                        </div>
                                                        @foreach($languages as $lang_key => $lang_name)
                                                            <input autocomplete="off"
                                                                   type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="payment_online_name_{{ $lang_key }}"
                                                                   id="payment_online_name_{{ $lang_key }}"
                                                                   value="{{ !empty($settings->{'payment_online_name_'.$lang_key}) ? $settings->{'payment_online_name_'.$lang_key} : trans('locale.Online') }}"
                                                                   style="border-radius: 0">
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">{{ trans('locale.Revolut') }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <fieldset>
                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="revolut" id="payment_methods_revolut" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('revolut', $settings->payment_methods) ? ' checked' : '' }}>
                                                                    <label for="payment_methods_revolut"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>
                                                        </div>
                                                        @foreach($languages as $lang_key => $lang_name)
                                                            <input autocomplete="off"
                                                                   type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="payment_revolut_name_{{ $lang_key }}"
                                                                   id="payment_revolut_name_{{ $lang_key }}"
                                                                   value="{{ !empty($settings->{'payment_revolut_name_'.$lang_key}) ? $settings->{'payment_revolut_name_'.$lang_key} : trans('locale.Revolut') }}"
                                                                   style="border-radius: 0">
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">BTCPay</div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <fieldset>
                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="btcpay" id="payment_methods_btcpay" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('btcpay', $settings->payment_methods) ? ' checked' : '' }}>
                                                                    <label for="payment_methods_btcpay"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>
                                                        </div>
                                                        @foreach($languages as $lang_key => $lang_name)
                                                            <input autocomplete="off"
                                                                   type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="payment_btcpay_name_{{ $lang_key }}"
                                                                   id="payment_btcpay_name_{{ $lang_key }}"
                                                                   value="{{ !empty($settings->{'payment_btcpay_name_'.$lang_key}) ? $settings->{'payment_btcpay_name_'.$lang_key} : 'BTCPay' }}"
                                                                   style="border-radius: 0">
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                        <div class="divider divider-dashed">
                                            <div class="divider-text">{{ trans('locale.MyCryptoCheckout') }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <fieldset>
                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="mycryptocheckout" id="payment_methods_mycryptocheckout" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('mycryptocheckout', $settings->payment_methods) ? ' checked' : '' }}>
                                                                    <label for="payment_methods_mycryptocheckout"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>
                                                        </div>
                                                        @foreach($languages as $lang_key => $lang_name)
                                                            <input autocomplete="off"
                                                                   type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="payment_mycryptocheckout_name_{{ $lang_key }}"
                                                                   id="payment_mycryptocheckout_name_{{ $lang_key }}"
                                                                   value="{{ !empty($settings->{'payment_mycryptocheckout_name_'.$lang_key}) ? $settings->{'payment_mycryptocheckout_name_'.$lang_key} : trans('locale.MyCryptoCheckout ') }}"
                                                                   style="border-radius: 0">
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <div class="collapsible collapse-icon accordion-icon-rotate">
                                                    <div class="card collapse-header">
                                                        <div id="headingCollapse1" class="card-header collapsed" data-toggle="collapse" role="button" data-target="#collapse1">
                                                            <span class="collapse-title">
                                                              <i class="bx bx-info-circle align-middle"></i>
                                                              <span class="align-middle">Status</span>
                                                            </span>
                                                        </div>
                                                        <div id="collapse1" role="tabpanel" class="collapse">
                                                            <div class="card-content">
                                                                <div class="card-body">
                                                                    @if(!empty($mycryptocheckout_data))
                                                                        <div class="row">
                                                                            <div class="col">
                                                                                <table class="table mb-0 dataTable no-footer" role="grid">
                                                                                    <tbody>
                                                                                    @foreach($mycryptocheckout_data as $param => $value)
                                                                                        <tr role="row" class="odd">
                                                                                            <td class="text-bold-600">{{ $param }}</td>
                                                                                            <td>{!! $value !!}</td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    @if($me->hasAccess(['settings.write']))
                                                                        <div class="row">
                                                                            <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                                                                <button type="button" id="refresh_mycryptocheckout_data" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                                                    <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                                                    Refresh your account data
                                                                                </button>
                                                                                <button type="button" id="delete_mycryptocheckout_data" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                                                    <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                                                    Delete account data
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card collapse-header">
                                                        <div id="headingCollapse6" class="card-header" data-toggle="collapse" role="button" data-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                                            <span class="collapse-title">
                                                              <i class="bx bx-wallet-alt align-middle"></i>
                                                              <span class="align-middle">Wallets</span>
                                                            </span>
                                                        </div>
                                                        <div id="collapse6" role="tabpanel" aria-labelledby="headingCollapse2" class="collapse">
                                                            <div class="card-content">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        <div class="col">
                                                                            @if(!empty($mycryptocheckout_wallets))
                                                                                <div class="wallets-container">
                                                                                    <table class="table table-hover table-striped wallets-table">
                                                                                        <thead>
                                                                                        <tr>
                                                                                            <th>Currency</th>
                                                                                            <th>Wallet Address</th>
                                                                                            <th>Details</th>
                                                                                            <th>Actions</th>
                                                                                        </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                        @foreach($mycryptocheckout_wallets as $wallet)
                                                                                            <tr class="wallet-row" data-id="{{ $wallet->id ?? '' }}">
                                                                                                <td>{{ $wallet->currency_id }}</td>
                                                                                                <td>{{ $wallet->address }}</td>
                                                                                                <td>{{ $wallet->details ?? '-' }}</td>
                                                                                                <td>
                                                                                                    <button type="button" class="btn btn-danger btn-sm remove-wallet">
                                                                                                        <i class="bx bx-trash"></i>
                                                                                                    </button>
                                                                                                </td>
                                                                                            </tr>
                                                                                        @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            @else
                                                                                <div class="wallets-container">
                                                                                    <div class="alert alert-info">No wallets added yet. Click the "Add" button to add a new wallet.</div>
                                                                                </div>
                                                                            @endif
                                                                            <button id="js_add_crypto_wallet" class="btn btn-primary mt-1" type="button"><i class="bx bx-plus"></i>
                                                                                {{ trans('locale.Add') }}
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{--
                                        <div class="row">
                                            <div class="col">
                                                <fieldset>
                                                    <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input autocomplete="off" type="checkbox" class="checkbox__input" value="mycryptocheckout " id="payment_methods_mycryptocheckout" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('mycryptocheckout', $settings->payment_methods) ? ' checked' : '' }}>
                                                                    <label for="payment_methods_mycryptocheckout"></label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ trans('locale.Display as') }}:</span>
                                                        </div>
                                                        @foreach($languages as $lang_key => $lang_name)
                                                            <input autocomplete="off"
                                                                   type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="payment_mycryptocheckout_name_{{ $lang_key }}"
                                                                   id="payment_mycryptocheckout_name_{{ $lang_key }}"
                                                                   value="{{ !empty($settings->{'payment_mycryptocheckout_name_'.$lang_key}) ? $settings->{'payment_mycryptocheckout_name_'.$lang_key} : trans('locale.MyCryptoCheckout ') }}"
                                                                   style="border-radius: 0">
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                        {{--<div class="divider divider-dashed">--}}
                                            {{--<div class="divider-text">LiqPay</div>--}}
                                        {{--</div>--}}
                                        {{--<div class="row">--}}
                                            {{--<div class="col">--}}
                                                {{--<fieldset>--}}
                                                    {{--<div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
                                                        {{--<div class="input-group-prepend">--}}
                                                            {{--<div class="input-group-text" style="padding: 0 5px 8px;">--}}
                                                                {{--<div class="checkbox checkbox-sm">--}}
                                                                    {{--<input autocomplete="off" type="checkbox" class="checkbox__input" value="liqpay" id="payment_methods_liqpay" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('liqpay', $settings->payment_methods) ? ' checked' : '' }}>--}}
                                                                    {{--<label for="payment_methods_liqpay"></label>--}}
                                                                {{--</div>--}}
                                                            {{--</div>--}}
                                                        {{--</div>--}}
                                                        {{--<div class="input-group-append">--}}
                                                            {{--<span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
                                                        {{--</div>--}}
                                                        {{--@foreach($languages as $lang_key => $lang_name)--}}
                                                            {{--<div class="input-group-append form-group" style="margin-bottom: 0;width: 100%;">--}}
                                                                {{--<input autocomplete="off"--}}
                                                                       {{--type="text"--}}
                                                                       {{--class="form-control form-control-sm"--}}
                                                                       {{--name="payment_liqpay_name_{{ $lang_key }}"--}}
                                                                       {{--id="payment_liqpay_name_{{ $lang_key }}"--}}
                                                                       {{--value="{{ !empty($settings->{'payment_liqpay_name_'.$lang_key}) ? $settings->{'payment_liqpay_name_'.$lang_key} : 'LiqPay' }}"--}}
                                                                       {{--style="border-radius: 0">--}}
                                                                {{--<div class="help-block" style="position: absolute;bottom: -36px;"></div>--}}
                                                            {{--</div>--}}
                                                        {{--@endforeach--}}
                                                    {{--</div>--}}
                                                {{--</fieldset>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                        {{--<div class="row">--}}
                                            {{--<div class="col">--}}
                                                {{--@include('admin.layouts.form.field-group', [--}}
                                                    {{--'type' => 'string',--}}
                                                    {{--'label' => trans('locale.Public API Key'),--}}
                                                    {{--'languages' => null,--}}
                                                    {{--'field' => [--}}
                                                     {{--'key' => 'liqpay_api_public_key',--}}
                                                     {{--'item' => $settings--}}
                                                    {{--]--}}
                                                {{--])--}}
                                            {{--</div>--}}
                                            {{--<div class="col">--}}
                                                {{--@include('admin.layouts.form.field-group', [--}}
                                                    {{--'type' => 'string',--}}
                                                    {{--'label' => trans('locale.Private API Key'),--}}
                                                    {{--'languages' => null,--}}
                                                    {{--'field' => [--}}
                                                     {{--'key' => 'liqpay_api_private_key',--}}
                                                     {{--'item' => $settings--}}
                                                    {{--]--}}
                                                {{--])--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                        {{--<div class="row">--}}
                                            {{--<div class="col">--}}
                                                {{--<div class="field-group">--}}
                                                    {{--<label>{{ trans('locale.Payment currency') }}</label>--}}
                                                    {{--<div class="form-group validate">--}}
                                                        {{--<select name="liqpay_api_currency" id="liqpay_api_currency" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
                                                            {{--<option value="0">{{ trans('locale.Not selected') }}</option>--}}
                                                            {{--@foreach($currencies as $currency)--}}
                                                                {{--<option value="{!! $currency !!}"--}}
                                                                        {{--@if ($currency == $settings->liqpay_api_currency)--}}
                                                                        {{--selected--}}
                                                                        {{--@endif--}}
                                                                {{-->{!! $currency !!}</option>--}}
                                                            {{--@endforeach--}}
                                                        {{--</select>--}}
                                                        {{--<div class="help-block"></div>--}}
                                                    {{--</div>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                            {{--<div class="col">--}}
                                                {{--<div class="field-group">--}}
                                                    {{--<label>{{ trans('locale.Test mode') }}</label>--}}
                                                    {{--<div class="form-group validate">--}}
                                                        {{--<select name="liqpay_api_sandbox" id="liqpay_api_sandbox" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
                                                            {{--@if(old('liqpay_api_sandbox') || !empty($settings->liqpay_api_sandbox))--}}
                                                                {{--<option value="1" selected>{{ trans('locale.Turn on') }}</option>--}}
                                                                {{--<option value="0">{{ trans('locale.Turn off') }}</option>--}}
                                                            {{--@elseif(!old('liqpay_api_sandbox') || empty($settings->liqpay_api_sandbox))--}}
                                                                {{--<option value="1">{{ trans('locale.Turn on') }}</option>--}}
                                                                {{--<option value="0" selected>{{ trans('locale.Turn off') }}</option>--}}
                                                            {{--@endif--}}
                                                        {{--</select>--}}
                                                        {{--<div class="help-block"></div>--}}
                                                    {{--</div>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                        {{--<div class="divider divider-dashed">--}}
                                            {{--<div class="divider-text">WayForPay</div>--}}
                                        {{--</div>--}}
                                        {{--<div class="row">--}}
                                            {{--<div class="col">--}}
                                                {{--<fieldset>--}}
                                                    {{--<div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">--}}
                                                        {{--<div class="input-group-prepend">--}}
                                                            {{--<div class="input-group-text" style="padding: 0 5px 8px;">--}}
                                                                {{--<div class="checkbox checkbox-sm">--}}
                                                                    {{--<input autocomplete="off" type="checkbox" class="checkbox__input" value="wayforpay" id="payment_methods_wayforpay" name="payment_methods[]"{{ !empty($settings->payment_methods) && in_array('wayforpay', $settings->payment_methods) ? ' checked' : '' }}>--}}
                                                                    {{--<label for="payment_methods_wayforpay"></label>--}}
                                                                {{--</div>--}}
                                                            {{--</div>--}}
                                                        {{--</div>--}}
                                                        {{--<div class="input-group-append">--}}
                                                            {{--<span class="input-group-text">{{ trans('locale.Display as') }}:</span>--}}
                                                        {{--</div>--}}
                                                        {{--@foreach($languages as $lang_key => $lang_name)--}}
                                                            {{--<div class="input-group-append form-group" style="margin-bottom: 0;width: 100%;">--}}
                                                                {{--<input autocomplete="off"--}}
                                                                       {{--type="text"--}}
                                                                       {{--class="form-control form-control-sm"--}}
                                                                       {{--name="payment_wayforpay_name_{{ $lang_key }}"--}}
                                                                       {{--id="payment_wayforpay_name_{{ $lang_key }}"--}}
                                                                       {{--value="{{ !empty($settings->{'payment_wayforpay_name_'.$lang_key}) ? $settings->{'payment_wayforpay_name_'.$lang_key} : 'WayForPay' }}"--}}
                                                                       {{--style="border-radius: 0">--}}
                                                                {{--<div class="help-block" style="position: absolute;bottom: -36px;"></div>--}}
                                                            {{--</div>--}}
                                                        {{--@endforeach--}}
                                                    {{--</div>--}}
                                                {{--</fieldset>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                        {{--<div class="row">--}}
                                            {{--<div class="col">--}}
                                                {{--@include('admin.layouts.form.field-group', [--}}
                                                    {{--'type' => 'string',--}}
                                                    {{--'label' => trans('locale.Account'),--}}
                                                    {{--'languages' => null,--}}
                                                    {{--'field' => [--}}
                                                     {{--'key' => 'wayforpay_account',--}}
                                                     {{--'item' => $settings--}}
                                                    {{--]--}}
                                                {{--])--}}
                                            {{--</div>--}}
                                            {{--<div class="col">--}}
                                                {{--@include('admin.layouts.form.field-group', [--}}
                                                    {{--'type' => 'string',--}}
                                                    {{--'label' => trans('locale.Token'),--}}
                                                    {{--'languages' => null,--}}
                                                    {{--'field' => [--}}
                                                     {{--'key' => 'wayforpay_secret',--}}
                                                     {{--'item' => $settings--}}
                                                    {{--]--}}
                                                {{--])--}}
                                            {{--</div>--}}
                                            {{--<div class="col">--}}
                                                {{--<div class="field-group">--}}
                                                    {{--<label>{{ trans('locale.Test mode') }}</label>--}}
                                                    {{--<div class="form-group validate">--}}
                                                        {{--<select name="wayforpay_sandbox" id="wayforpay_sandbox" autocomplete="off" class="form-control form-control-sm" aria-invalid="false">--}}
                                                            {{--@if(old('wayforpay_sandbox') || !empty($settings->wayforpay_sandbox))--}}
                                                                {{--<option value="1" selected>{{ trans('locale.Turn on') }}</option>--}}
                                                                {{--<option value="0">{{ trans('locale.Turn off') }}</option>--}}
                                                            {{--@elseif(!old('wayforpay_sandbox') || empty($settings->wayforpay_sandbox))--}}
                                                                {{--<option value="1">{{ trans('locale.Turn on') }}</option>--}}
                                                                {{--<option value="0" selected>{{ trans('locale.Turn off') }}</option>--}}
                                                            {{--@endif--}}
                                                        {{--</select>--}}
                                                        {{--<div class="help-block"></div>--}}
                                                    {{--</div>--}}
                                                {{--</div>--}}
                                            {{--</div>--}}
                                        {{--</div>--}}
                                    </div>
                                    @if($me->hasAccess(['settings.write']))
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- settings edit payment form ends -->
                        </div>
                        <div class="tab-pane fade show" id="contacts" aria-labelledby="contacts-tab" role="tabpanel">
                            <!-- settings edit contacts form start -->
                            <form action="/admin/shop/settings/contacts" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                <div class="divider divider-dashed">
                                                    <div class="divider-text">{{ trans('locale.Contact phone numbers') }}</div>
                                                </div>
                                                <div class="phones-repeater">
                                                    <div data-repeater-list="phones">
                                                        @if(!empty($settings->phones))
                                                            @foreach($settings->phones as $row)
                                                                <div data-repeater-item="">
                                                                    <fieldset>
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control" name="phone" value="{{ $row->phone }}" placeholder="{{ trans('locale.Phone number') }}" autocomplete="off">
                                                                            <div class="input-group-append">
                                                                                <button class="btn btn-danger" data-repeater-delete="" type="button">{{ trans('locale.Delete') }}</button>
                                                                            </div>
                                                                        </div>
                                                                    </fieldset>
                                                                    <hr>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div data-repeater-item="">
                                                                <fieldset>
                                                                    <div class="input-group">
                                                                        <input type="text" class="form-control" name="phone" value="" placeholder="{{ trans('locale.Phone number') }}" autocomplete="off">
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-danger" data-repeater-delete="" type="button">{{ trans('locale.Delete') }}</button>
                                                                        </div>
                                                                    </div>
                                                                </fieldset>
                                                                <hr>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col p-0">
                                                            <button class="btn btn-primary" data-repeater-create type="button"><i class="bx bx-plus"></i>
                                                                {{ trans('locale.Add') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="divider divider-dashed">
                                                    <div class="divider-text">{{ trans('locale.Email addresses') }}</div>
                                                </div>
                                                <div class="emails-repeater">
                                                    <div data-repeater-list="emails">
                                                        @if(!empty($settings->emails))
                                                            @foreach($settings->emails as $key => $row)
                                                                <div data-repeater-item="">
                                                                    <fieldset>
                                                                        <div class="input-group">
                                                                            <div class="input-group-prepend">
                                                                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                                    <i class="bx {{ $row->destination == 'all' ? 'bxs-envelope' : ($row->destination == 'contacts' ? 'bx-conversation' : 'bx-mail-send') }}"></i>
                                                                                </button>
                                                                                <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38.8px, 0px);">
                                                                                    <button class="dropdown-item" type="button" data-value="orders" data-icon="bx-mail-send">{{ trans('locale.For applications') }}</button>
                                                                                    <button class="dropdown-item" type="button" data-value="contacts" data-icon="bx-conversation">{{ trans('locale.For contacts') }}</button>
                                                                                    <button class="dropdown-item" type="button" data-value="all" data-icon="bxs-envelope">{{ trans('locale.For everything') }}</button>
                                                                                </div>
                                                                                <input type="hidden" class="js_email_destination" name="emails[{{ $key }}][destination]" value="{{ $row->destination }}" autocomplete="off">
                                                                            </div>
                                                                            <input type="email" class="form-control" name="emails[{{ $key }}][email]" value="{{ $row->email }}" placeholder="E-mail" autocomplete="off">
                                                                            <div class="input-group-append">
                                                                                <button class="btn btn-danger" data-repeater-delete="" type="button">{{ trans('locale.Delete') }}</button>
                                                                            </div>
                                                                        </div>
                                                                    </fieldset>
                                                                    <hr>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div data-repeater-item="">
                                                                <fieldset>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                                <i class="bx bx-mail-send"></i>
                                                                            </button>
                                                                            <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38.8px, 0px);">
                                                                                <button class="dropdown-item" type="button" data-value="orders" data-icon="bx-mail-send">{{ trans('locale.For applications') }}</button>
                                                                                <button class="dropdown-item" type="button" data-value="contacts" data-icon="bx-conversation">{{ trans('locale.For contacts') }}</button>
                                                                                <button class="dropdown-item" type="button" data-value="all" data-icon="bxs-envelope">{{ trans('locale.For everything') }}</button>
                                                                            </div>
                                                                            <input class="js_email_destination" type="hidden" name="emails[0][destination]" value="orders" autocomplete="off">
                                                                        </div>
                                                                        <input type="email" class="form-control" name="emails[0][email]" value="" placeholder="E-mail" autocomplete="off">
                                                                        <div class="input-group-append">
                                                                            <button class="btn btn-danger" data-repeater-delete="" type="button">{{ trans('locale.Delete') }}</button>
                                                                        </div>
                                                                    </div>
                                                                </fieldset>
                                                                <hr>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="col p-0">
                                                            <button class="btn btn-primary" data-repeater-create type="button"><i class="bx bx-plus"></i>
                                                                {{ trans('locale.Add') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['settings.write']))
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- settings edit contacts form ends -->
                        </div>
                        <div class="tab-pane fade show" id="trustpilot" aria-labelledby="trustpilot-tab" role="tabpanel">
                            <form action="/admin/shop/settings/trustpilot" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                <div class="field-group">
                                                    <label>Rating</label>
                                                    <div class="form-group validate">
                                                        <input type="text" class="form-control form-control-sm" name="trustpilot_rating" value="{{ isset($settings->trustpilot_rating) ? $settings->trustpilot_rating : '' }}" autocomplete="off">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="field-group">
                                                    <label>Rating name</label>
                                                    <div class="form-group validate">
                                                        <input type="text" class="form-control form-control-sm" name="trustpilot_rating_name" value="{{ isset($settings->trustpilot_rating_name) ? $settings->trustpilot_rating_name : '' }}" autocomplete="off">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <div class="field-group">
                                                    <label>Total reviews</label>
                                                    <div class="form-group validate">
                                                        <input type="text" class="form-control form-control-sm" name="trustpilot_total_reviews" value="{{ isset($settings->trustpilot_total_reviews) ? $settings->trustpilot_total_reviews : '' }}" autocomplete="off">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="field-group">
                                                    <label>Trustpilot link</label>
                                                    <div class="form-group validate">
                                                        <input type="text" class="form-control form-control-sm" name="trustpilot_trustpilot_link" value="{{ isset($settings->trustpilot_trustpilot_link) ? $settings->trustpilot_trustpilot_link : '' }}" autocomplete="off">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['settings.write']))
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade show" id="maintenance" aria-labelledby="maintenance-tab" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Maintenance Mode</h4>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <div class="alert-body">
                                            <i class="bx bx-info-circle"></i>
                                            <span>When maintenance mode is enabled, your site will display a maintenance message to visitors. Only administrators will be able to access the site.</span>
                                        </div>
                                    </div>

                                    <div class="maintenance-status text-center py-2">
                                        @if(app()->isDownForMaintenance())
                                            <div class="alert alert-warning">
                                                <h4 class="alert-heading">Maintenance Mode Active</h4>
                                                <p>Your site is currently in maintenance mode.</p>
                                                <p>To access the site, use this secret URL: <code>{{ url('/admin-access') }}</code></p>
                                            </div>
                                            <button type="button" class="btn btn-primary" id="disable-maintenance">
                                                <i class="bx bx-power-off mr-25"></i> Disable Maintenance Mode
                                            </button>
                                        @else
                                            <div class="alert alert-success">
                                                <h4 class="alert-heading">Maintenance Mode Inactive</h4>
                                                <p>Your site is currently live and accessible to everyone.</p>
                                            </div>
                                            <button type="button" class="btn btn-warning" id="enable-maintenance">
                                                <i class="bx bx-cog mr-25"></i> Enable Maintenance Mode
                                            </button>
                                        @endif
                                    </div>

                                    <div class="card mt-2">
                                        <div class="card-header">
                                            <h4 class="card-title">Maintenance Settings</h4>
                                        </div>
                                        <div class="card-body">
                                            <form id="maintenance-settings-form">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="maintenance_message">Custom Maintenance Message</label>
                                                    <textarea class="form-control" id="maintenance_message" name="message" rows="3" placeholder="Enter custom maintenance message...">@isset($maintenanceSettings['message']){{ $maintenanceSettings['message'] }}@endisset</textarea>
                                                    <small class="text-muted">This message will be displayed to visitors when maintenance mode is active.</small>
                                                </div>
                                                <div class="form-group">
                                                    <label for="maintenance_retry">Retry After (seconds)</label>
                                                    <input type="number" class="form-control" id="maintenance_retry" name="retry" value="{{ $maintenanceSettings['retry'] ?? 60 }}">
                                                    <small class="text-muted">The number of seconds after which the browser should retry accessing the site.</small>
                                                </div>
                                                <div class="form-group">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="maintenance_refresh" name="refresh" value="1">
                                                        <label class="custom-control-label" for="maintenance_refresh">Refresh page after enabling/disabling maintenance mode</label>
                                                    </div>
                                                </div>
                                                <div class="text-right mt-2">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bx bx-save mr-25"></i> Save Settings
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- settings edit ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/repeater/jquery.repeater.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/settings.js')}}"></script>
@endsection
