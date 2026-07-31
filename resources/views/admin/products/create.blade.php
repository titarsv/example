@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Create product'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/products.css')}}">
@endsection

@section('content')
    <!-- product edit start -->
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
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- product edit Info form start -->
                            <form action="/admin/products/create" method="post" class="js_ajax_form" id="js_product_form" novalidate>
                                {!! csrf_field() !!}
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
                                                <div class="field-group">
                                                    <div class="row">
                                                        <div class="col-sm-3">
                                                            <label>{{ trans('locale.Base price') }}</label>
                                                            @include('admin.layouts.form.string', [
                                                             'key' => 'original_price',
                                                            ])
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Sale price') }}</label>
                                                            <fieldset>
                                                                <div class="input-group input-group-sm">
                                                                    <div class="input-group-prepend">
                                                                        <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                            <div class="checkbox checkbox-sm">
                                                                                <input type="checkbox" class="checkbox__input " id="checkboxinput" value="1" name="sale"{{ old('sale') ? ' checked' : '' }}>
                                                                                <label for="checkboxinput"></label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <input type="text" aria-label="{{ trans('locale.Discounted price') }}" class="form-control form-control-sm" placeholder="{{ trans('locale.Discounted price') }}" name="sale_price" value="{{ old('sale_price') }}">
                                                                    <div class="input-group-append">
                                                                        <div class="position-relative has-icon-left" style="min-width: 250px;margin-left: -1px;">
                                                                            <input type="text" class="form-control form-control-sm pickadate" placeholder="{{ trans('locale.Start date') }}" name="sale_from" value="{{ old('sale_from') }}" style="border-radius: 0">
                                                                            <div class="form-control-position">
                                                                                <i class='bx bx-calendar' style="margin-top: 9px;"></i>
                                                                            </div>
                                                                        </div>
                                                                        <div class="position-relative has-icon-left" style="min-width: 250px;margin-left: -1px;">
                                                                            <input type="text" class="form-control form-control-sm pickadate" placeholder="{{ trans('locale.End date') }}" name="sale_to" value="{{ old('sale_to') }}" style="border-bottom-left-radius: 0;border-top-left-radius: 0">
                                                                            <div class="form-control-position">
                                                                                <i class='bx bx-calendar' style="margin-top: 9px;"></i>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="field-group">
                                                    <div class="row">
                                                        <div class="col">
                                                            <label>{{ trans('locale.SKU') }}</label>
                                                            @include('admin.layouts.form.string', [
                                                             'key' => 'sku'
                                                            ])
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Availability') }}</label>
                                                            @include('admin.layouts.form.select', [
                                                             'key' => 'stock',
                                                             'options' => [(object)['value' => '1', 'name' => trans('locale.In stock')], (object)['value' => '-2', 'name' => trans('locale.Out of stock')], (object)['value' => '0', 'name' => trans('locale.Expected')], (object)['value' => '-1', 'name' => trans('locale.Preorder')]],
                                                             'selected' => [old('stock')]
                                                            ])
{{--                                                        @include('admin.layouts.form.string', [--}}
{{--                                                          'key' => 'stock',--}}
{{--                                                          'required' => true,--}}
{{--                                                          'languages' => null--}}
{{--                                                        ])--}}
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Sorting priority') }}</label>
                                                            @include('admin.layouts.form.string', [
                                                             'key' => 'sort_priority'
                                                            ])
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="row">
                                                        <div class="col">
                                                            @include('admin.layouts.form.field-group', [
                                                                'type' => 'select2',
                                                                'label' => trans('locale.Product categories'),
                                                                'field' => [
                                                                  'key' => 'product_category_id',
                                                                  'multiple' => true,
                                                                  'required' => true,
                                                                  'options' => $categories,
                                                                  'selected' => []
                                                                ]
                                                            ])
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="row">
                                                        <div class="col-sm-3">
                                                            <label>{{ trans('locale.Main image') }}</label>
                                                            @include('admin.layouts.form.image', [
                                                             'key' => 'file_id'
                                                            ])
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Gallery') }}</label>
                                                            @include('admin.layouts.form.gallery', [
                                                             'key' => 'gallery',
                                                             'gallery' => []
                                                            ])
                                                        </div>
                                                    </div>
                                                </div>
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'editor',
                                                    'label' => trans('locale.Product description'),
                                                    'field' => [
                                                      'key' => 'description'
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['products.write']))
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
                            <!-- product edit Info form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- product edit ends -->

    @include('admin.layouts.mce', ['editors' => $editors])
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/legacy.js')}}"></script>
    <script src="{{asset('vendors/js/forms/repeater/jquery.repeater.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/products.js')}}"></script>
    @include('admin.media.assets')
@endsection
