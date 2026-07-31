@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit attribute'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/pages/page-categories.css')}}">
@endsection

@section('content')
    <!-- attribute edit start -->
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
                            <a class="nav-link d-flex align-items-center btn-sm" id="values-tab" data-toggle="tab"
                               href="#values" aria-controls="values" role="tab" aria-selected="false">
                                <i class="bx bxs-truck mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Values') }}</span>
                            </a>
                        </li>
                        <li class="custom-control custom-switch custom-switch-success">
                            <p class="mb-0 mr-1">{{ trans('locale.Use as filter') }}</p>
                            <input type="checkbox" class="custom-control-input js_change_status" data-endpoint="products/attributes/filter"
                                   name="status" value="1" form="attribute_form" id="js_attribute_status"
                                   data-id="{{ $attribute->id }}" autocomplete="off"{{ $attribute->is_filter ? ' checked' : '' }}>
                            <label class="custom-control-label" for="js_attribute_status">
                                <span class="switch-icon-left"><i class="bx bx-check"></i></span>
                                <span class="switch-icon-right"><i class="bx bx-x"></i></span>
                            </label>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- category edit Info form start -->
                            <form action="/admin/products/attributes/edit/{{ $attribute->id }}" method="post" class="js_ajax_form" id="attribute_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col">
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'string',
                                            'label' => trans('locale.Attribute name'),
                                            'field' => [
                                            'key' => 'name',
                                            'item' => $attribute,
                                            'required' => true
                                            ]
                                        ])
                                    </div>
                                </div>
                                <div class="row js_filter_data{{ $attribute->is_filter ? '' : ' hidden' }}">
                                    <div class="col">
                                        @include('admin.layouts.form.field-group', [
                                           'type' => 'string',
                                           'label' => trans('locale.Filter name'),
                                           'field' => [
                                           'key' => 'filter_name',
                                           'item' => $attribute,
                                           'required' => false
                                           ]
                                        ])
                                        <div class="field-group">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Slug') }}</label>
                                                        @include('admin.layouts.form.string', [
                                                        'key' => 'slug',
                                                        'item' => $attribute,
                                                        'required' => false
                                                        ])
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Type') }}</label>
                                                        @include('admin.layouts.form.select', [
                                                        'key' => 'type',
                                                        'options' => $types,
                                                        'multiple' => false,
                                                        'selected' => [old('type', $attribute->type)],
                                                        'disabled' => $attribute->is_numeric_values ? [] : ['range', 'range_slider']
                                                        ])
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="field-group">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Value type') }}</label>
                                                        @include('admin.layouts.form.select', [
                                                        'key' => 'is_numeric_values',
                                                        'options' => [(object)['value' => 0, 'name' => trans('locale.Textual')], (object)['value' => 1, 'name' => trans('locale.Numeric')], (object)['value' => 2, 'name' => trans('locale.Color')], (object)['value' => 3, 'name' => trans('locale.Image')]],
                                                        'multiple' => false,
                                                        'selected' => [old('is_numeric_values', $attribute->is_numeric_values)]
                                                        ])
                                                    </div>
                                                </div>
                                                <div class="col{{ old('is_numeric_values', $attribute->is_numeric_values) ? '' : ' hidden' }}" id="js_numeric_unit">
                                                    <div class="form-group">
                                                        <label>{{ trans('locale.Unit of measurement') }}</label>
                                                        @include('admin.layouts.form.string', [
                                                        'key' => 'unit',
                                                        'item' => $attribute,
                                                        'required' => false
                                                        ])
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="field-group">
                                            <div class="row">
                                                <div class="col">
                                                    <label>{{ trans('locale.Display on product page') }}</label>
                                                    <div class="custom-control custom-switch d-flex align-items-center" style="height: 31px;">
                                                        <span>{{ trans('locale.No') }}</span>
                                                        <input type="checkbox" class="custom-control-input" name="visible" value="1" id="visible"{{ old('visible', $attribute->visible) ? ' checked' : '' }}>
                                                        <label class="custom-control-label ml-1 mr-1" for="visible">
                                                        </label>
                                                        <span>{{ trans('locale.Yes') }}</span>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <label>{{ trans('locale.Required for all products') }}</label>
                                                    <div class="custom-control custom-switch d-flex align-items-center" style="height: 31px;">
                                                        <span>{{ trans('locale.No') }}</span>
                                                        <input type="checkbox" class="custom-control-input" name="required_for_all" value="1" id="required_for_all"{{ old('required_for_all', $attribute->required_for_all) ? ' checked' : '' }}>
                                                        <label class="custom-control-label ml-1 mr-1" for="required_for_all">
                                                        </label>
                                                        <span>{{ trans('locale.Yes') }}</span>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <label>{{ trans('locale.Use for variations') }}</label>
                                                    <div class="custom-control custom-switch d-flex align-items-center" style="height: 31px;">
                                                        <span>{{ trans('locale.No') }}</span>
                                                        <input type="checkbox" class="custom-control-input" name="is_variation_attribute" value="1" id="is_variation_attribute"{{ old('is_variation_attribute', $attribute->is_variation_attribute) ? ' checked' : '' }}>
                                                        <label class="custom-control-label ml-1 mr-1" for="is_variation_attribute">
                                                        </label>
                                                        <span>{{ trans('locale.Yes') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                        @if($me->hasAccess(['attributes.write']))
                                        <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                            <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                            {{ trans('locale.Save changes') }}
                                        </button>
                                        @endif
                                        <button type="button" class="btn btn-light" onclick="window.history.back()">{{ trans('locale.Cancel') }}</button>
                                    </div>
                                </div>
                            </form>
                            <!-- category edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="values" aria-labelledby="values-tab" role="tabpanel">
                            <!-- attribute edit values form start -->
                            <form action="/admin/products/attributes/values/{{ $attribute->id }}" method="post" class="js_ajax_form js_as_json values-repeater" novalidate>
                                <div id="js_attribute_values_wrapper">
                                    @include('admin.products.attributes.values')
                                </div>
                                <div class="form-group">
                                    <div class="col p-0">
                                        <button class="btn btn-primary" data-repeater-create type="button"><i class="bx bx-plus"></i>
                                            {{ trans('locale.Add') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                        @if($me->hasAccess(['attributes.write']))
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-light" onclick="window.history.back()">{{ trans('locale.Cancel') }}</button>
                                    </div>
                                </div>
                            </form>
                            <!-- attribute edit values form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- attribute edit ends -->
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
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/admin/attributes.js')}}"></script>
    @include('admin.media.assets')
@endsection
