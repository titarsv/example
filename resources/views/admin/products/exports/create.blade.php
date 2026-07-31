@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Export creation'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/exports.css')}}">
@endsection

@section('content')
    <!-- export edit start -->
    <section class="export-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="settings-tab" data-toggle="tab"
                               href="#settings" aria-controls="settings" role="tab" aria-selected="false">
                                <i class="bx bx-slider-alt mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Settings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item disabled">
                            <a class="nav-link d-flex align-items-center btn-sm disabled" id="fields-tab" data-toggle="tab"
                               href="#fields" aria-controls="fields" role="tab" aria-selected="false">
                                <i class="bx bx-menu mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Exported fields') }}</span>
                            </a>
                        </li>
                        <li class="nav-item disabled">
                            <a class="nav-link d-flex align-items-center btn-sm disabled" id="filter-tab" data-toggle="tab"
                                  href="#filter" aria-controls="filter" role="tab" aria-selected="false">
                                <i class="bx bx-filter mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Products filter') }}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="settings" aria-labelledby="settings-tab" role="tabpanel">
                            <!-- export edit Info form start -->
                            <form action="/admin/products/exports/create" method="post" class="js_ajax_form" id="js_export_settings_form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Name'),
                                                    'field' => [
                                                      'key' => 'name',
                                                      'required' => true
                                                    ]
                                                ])
                                                <div class="form-group">
                                                    <label>Url</label>
                                                    <fieldset>
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">{{ ENV('APP_URL') }}/exports/</span>
                                                            </div>
                                                            <input type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="url"
                                                                   value="{{ old('url') }}"/>
                                                            <div class="input-group-append">
                                                                <span class="input-group-text" id="js_extension">.csv</span>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>
                                            </div>
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                   'type' => 'select',
                                                   'label' => trans('locale.Type'),
                                                   'field' => [
                                                     'key' => 'type',
                                                     'selected' => [],
                                                     'options' => [(object)['value' => 'csv', 'name' => 'csv'], (object)['value' => 'xls', 'name' => 'xls'], (object)['value' => 'xml', 'name' => 'xml'], (object)['value' => 'rss', 'name' => 'rss'], (object)['value' => 'json', 'name' => 'json']],
                                                     'required' => true
                                                   ]
                                                ])
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'select',
                                                    'label' => trans('locale.Update frequency'),
                                                    'field' => [
                                                      'key' => 'schedule',
                                                      'selected' => [],
                                                      'options' => [
                                                        (object)['value' => '', 'name' => trans('locale.do not update')],
                                                        (object)['value' => 'everyMinute', 'name' => trans('locale.every minute')],
                                                        (object)['value' => 'everyFiveMinutes', 'name' => trans('locale.every five minutes')],
                                                        (object)['value' => 'everyTenMinutes', 'name' => trans('locale.every ten minutes')],
                                                        (object)['value' => 'everyThirtyMinutes', 'name' => trans('locale.every thirty minutes')],
                                                        (object)['value' => 'hourly', 'name' => trans('locale.every hour')],
                                                        (object)['value' => 'daily', 'name' => trans('locale.every day')],
                                                        (object)['value' => 'weekly', 'name' => trans('locale.every week')],
                                                        (object)['value' => 'monthly', 'name' => trans('locale.every month')],
                                                        (object)['value' => 'quarterly', 'name' => trans('locale.quarterly')],
                                                        (object)['value' => 'yearly', 'name' => trans('locale.yearly')]
                                                      ],
                                                      'required' => false
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['exports.write']))
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- export edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="fields" aria-labelledby="fields-tab" role="tabpanel">
                            <!-- export edit Info form start -->
                            <form action="" method="post" method="post" class="js_ajax_form fields-repeater" id="js_export_fields_form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                <div class="row justify-content-between">
                                                    <div class="col" style="flex-grow: 1;">
                                                        <label>{{ trans('locale.Name') }}</label>
                                                    </div>
                                                    <div class="col" style="flex-grow: 1;">
                                                        <label>{{ trans('locale.Field') }}</label>
                                                    </div>
                                                    <div class="col" style="flex-grow: 1;">
                                                        <label>{{ trans('locale.Modifiers') }}</label>
                                                    </div>
                                                    <div class="col" style="flex-grow: 0; min-width: 127px;">

                                                    </div>
                                                </div>
                                                <div id="js_attribute_values_wrapper">
                                                    <div data-repeater-list="fields">
                                                        <div class="row js_export_field" data-repeater-item="">
                                                            <div class="form-element col" style="flex-grow: 1;">
                                                                <input type="text" class="form-control form-control-sm" name="fields[0][name]" placeholder="{{ trans('locale.Name') }}" value="{{ old('fields[0][name]') }}" autocomplete="off" />
                                                            </div>
                                                            <div class="form-element col" style="flex-grow: 1;">
                                                                <select class="form-control form-control-sm field_type" name="fields[0][field][type]" autocomplete="off">
                                                                    @foreach($field_types as $field_type => $name)
                                                                        <option value="{{ $field_type }}">{{ $name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="form-element col modifications fields-modifications-repeater" style="flex-grow: 1;">
                                                                <div class="modifications" data-repeater-list="modifications">
                                                                    <fieldset data-repeater-item="">
                                                                        <div class="input-group input-group-sm mb-1">
                                                                            <select class="form-control form-control-sm" name="fields[0][modifications][0][type]" autocomplete="off">
                                                                                @foreach($modifications as $key => $modification)
                                                                                    <option value="{{ $key }}">{{ $modification }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                            <div class="input-group-append">
                                                                                <button type="button" class="btn btn-danger btn-sm" data-repeater-delete="">
                                                                                    <i class="bx bx-minus-circle"></i>
                                                                                </button>
                                                                                <button type="button" class="btn btn-primary btn-sm" data-repeater-create="">
                                                                                    <i class="bx bxs-plus-square"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </fieldset>
                                                                </div>
                                                            </div>
                                                            <div class="form-element col text-right" style="flex-grow: 0; min-width:127px;">
                                                                <button class="btn btn-danger btn-sm text-nowrap px-1" data-repeater-delete="" type="button"> <i class="bx bx-x"></i>
                                                                    {{ trans('locale.Delete') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['exports.write']))
                                        <div class="col-12 mt-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    <button class="btn btn-primary" data-repeater-create="" type="button"><i class="bx bx-plus"></i>
                                                        {{ trans('locale.Add') }}
                                                    </button>
                                                </div>
                                                <div class="col-6 d-flex flex-sm-row flex-column justify-content-end">
                                                    <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                        {{ trans('locale.Save changes') }}
                                                    </button>
                                                    <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- export edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="filter" aria-labelledby="filter-tab" role="tabpanel">
                            <!-- export edit Info form start -->
                            <form action="" method="post" class="js_ajax_form filter_form" id="js_export_filter_form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col">
                                        <div class="panel panel-default">
                                            <div class="panel-body"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    @if($me->hasAccess(['exports.write']))
                                        <div class="col-12 mt-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-primary" id="js_add_condition_group"><i class="bx bx-plus"></i>
                                                        {{ trans('locale.Add a condition group') }}
                                                    </button>
                                                </div>
                                                <div class="col-6 d-flex flex-sm-row flex-column justify-content-end">
                                                    <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                        {{ trans('locale.Save changes') }}
                                                    </button>
                                                    <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- export edit Info form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- category edit ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/repeater/jquery.repeater.js')}}"></script>
    <script src="{{asset('vendors/js/forms/spinner/jquery.bootstrap-touchspin.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script>
        window.categories = {!! json_encode($categories) !!};
        window.actions = {!! json_encode($actions) !!};
        window.attributes = {!! json_encode($all_attributes) !!};
    </script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/admin/exports.js')}}"></script>
    @include('admin.media.assets')
@endsection
