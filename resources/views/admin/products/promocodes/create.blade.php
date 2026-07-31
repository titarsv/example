@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Create promocode'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/promocodes.css')}}">
@endsection

@section('content')
    <!-- promocode edit start -->
    <section class="promocode-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="settings-tab" data-toggle="tab"
                               href="#settings" aria-controls="settings" role="tab" aria-selected="false">
                                <i class="bx bx-slider-alt mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Main information') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="filter-tab" data-toggle="tab"
                                  href="#filter" aria-controls="filter" role="tab" aria-selected="false">
                                <i class="bx bx-filter mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Usage restrictions') }}</span>
                            </a>
                        </li>
{{--                        <li class="nav-item disabled">--}}
{{--                            <a class="nav-link d-flex align-items-center btn-sm disabled" id="filter-tab" data-toggle="tab"--}}
{{--                                  href="#filter" aria-controls="filter" role="tab" aria-selected="false">--}}
{{--                                <i class="bx bx-filter mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Usage restrictions') }}</span>--}}
{{--                            </a>--}}
{{--                        </li>--}}
                    </ul>
                    <form action="/admin/products/promocodes/create" method="post" class="js_ajax_form" id="js_promocodes_settings_form" novalidate>
                        @csrf
                        <div class="tab-content">
                            <div class="tab-pane active fade show" id="settings" aria-labelledby="settings-tab" role="tabpanel">
                                <!-- promocode edit Info form start -->
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
                                            </div>
                                            <div class="col">
                                                <div class="form-group">
                                                    <label>{{ trans('locale.Discount size') }}</label>
                                                    <fieldset>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control form-control-sm" id="js_sale" name="percent" value="" data-validation-required-message="{{ trans('locale.This field is required') }}" aria-invalid="false" required>
                                                            <div class="input-group-append" id="js_coupon_price">
                                                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">%</button>
                                                                <div class="dropdown-menu dropdown-menu-right" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(428px, 38.8px, 0px);">
                                                                    <a class="dropdown-item" href="#" data-name="percent">%</a>
                                                                    <a class="dropdown-item" href="#" data-name="price">{{ Helper::getDefaultCurrencySymbol() }}</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <div class="form-group">
                                                    <label>{{ trans('locale.Promo code') }}</label>
                                                    <fieldset>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" id="code" name="code" value="" autocomplete="off" data-validation-required-message="{{ trans('locale.This field is required') }}" aria-invalid="false" required>
                                                            <div class="input-group-append" id="js_generate_code">
                                                                <button class="btn btn-primary" type="button"><i class="bx bx-revision"></i></button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['coupons.write']))
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="tab-pane fade show" id="filter" aria-labelledby="filter-tab" role="tabpanel">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>{{ trans('locale.Promocode applies to') }}</label>
                                            </div>
                                            <div class="col-md-9 form-group">
                                                <fieldset>
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="d-inline-block mr-2 mb-1">
                                                            <fieldset>
                                                                <div class="radio">
                                                                    <input type="radio" name="scope" value="all" id="radio_all" autocomplete="off" checked>
                                                                    <label for="radio_all">{{ trans('locale.All products') }}</label>
                                                                </div>
                                                            </fieldset>
                                                        </li>
                                                        <li class="d-inline-block mr-2 mb-1">
                                                            <fieldset>
                                                                <div class="radio">
                                                                    <input type="radio" name="scope" value="categories" autocomplete="off" id="radio_categories">
                                                                    <label for="radio_categories">{{ trans('locale.Selected categories') }}</label>
                                                                </div>
                                                            </fieldset>
                                                        </li>
                                                        <li class="d-inline-block mb-1">
                                                            <fieldset>
                                                                <div class="radio">
                                                                    <input type="radio" name="scope" value="products" autocomplete="off" id="radio_products">
                                                                    <label for="radio_products">{{ trans('locale.Selected products') }}</label>
                                                                </div>
                                                            </fieldset>
                                                        </li>
                                                    </ul>
                                                </fieldset>
                                            </div>
                                            <div class="form-group col-md-9 offset-md-3 js_categories"{!! old('scope', 'all') != 'categories' ? ' style="display: none"' : '' !!}>
                                                <input type="hidden" name="scope_categories" value="{{ old('scope_categories') }}" autocomplete="off">
                                                <button type="button" id="js_add_categories" class="btn btn-primary">{{ trans('locale.Add category') }}</button>
                                                <span id="js_categories_count" class="ml-1">{{ trans('locale.Selected categories count', ['count' => 0]) }}</span> <i id="clear_categories" class="glyphicon glyphicon-trash" style="cursor: pointer;"></i>
                                            </div>
                                            <div class="form-group col-md-9 offset-md-3 js_products"{!! old('scope', 'all') != 'products' ? ' style="display: none"' : '' !!}>
                                                <input type="hidden" name="scope_products" value="{{ old('scope_products') }}" autocomplete="off">
                                                <button type="button" id="js_add_products" class="btn btn-primary">{{ trans('locale.Add products') }}</button>
                                                <span id="js_products_count" class="ml-1">{{ trans('locale.Selected products count', ['count' => 0]) }}</span> <i id="clear_products" class="glyphicon glyphicon-trash" style="cursor: pointer;"></i>
                                            </div>
                                            <div class="form-group col-md-9 offset-md-3">
                                                <fieldset>
                                                    <div class="checkbox checkbox-sm">
                                                        <input type="checkbox" name="disposable" value="1" class="checkbox-input" autocomplete="off" id="checkbox_disposable">
                                                        <label for="checkbox_disposable">{{ trans('locale.Can be used unlimited times') }}</label>
                                                    </div>
                                                </fieldset>
                                            </div>
                                            <div class="form-group col-md-9 offset-md-3">
                                                <fieldset>
                                                    <div class="checkbox checkbox-sm">
                                                        <input type="checkbox" name="min_total_toggle" value="1" class="checkbox-input" autocomplete="off" id="checkbox_min_total_toggle">
                                                        <label for="checkbox_min_total_toggle">{{ trans('locale.Discount applies with minimum order total') }}</label>
                                                    </div>
                                                </fieldset>
                                            </div>
                                            <div class="form-group col-md-9 offset-md-3">
                                                <fieldset>
                                                    <div class="checkbox checkbox-sm">
                                                        <input type="checkbox" name="without_sale" value="1" class="checkbox-input" autocomplete="off" id="checkbox_without_sale">
                                                        <label for="checkbox_without_sale">{{ trans('locale.Do not apply to products with active discount') }}</label>
                                                    </div>
                                                </fieldset>
                                            </div>
                                            <div class="col-md-3">
                                                <label>{{ trans('locale.Promocode validity period') }}</label>
                                            </div>
                                            <div class="col-md-9 form-group">
                                                <fieldset>
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend">
                                                            <div class="input-group-text" style="padding: 0 5px 0;">
                                                                <div class="checkbox checkbox-sm">
                                                                    <input type="checkbox" class="checkbox__input" id="js_with_end_date">
                                                                    <label for="js_with_end_date" style="line-height: 21px;font-weight: 400;font-size: 12px;padding-right: 5px;">{{ trans('locale.Set end date') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="input-group-append">
                                                            <div class="position-relative has-icon-left" style="min-width: 250px;margin-left: -1px;">
                                                                <input type="text"
                                                                       class="form-control form-control-sm pickadate"
                                                                       placeholder="{{ trans('locale.Start date') }}"
                                                                       name="sale_from"
                                                                       data-value="{{ old('sale_from') }}"
                                                                       value="{{ old('sale_from') }}"
                                                                       style="border-radius: 0">
                                                                <div class="form-control-position">
                                                                    <i class='bx bx-calendar' style="margin-top: 9px;"></i>
                                                                </div>
                                                            </div>
                                                            <div class="position-relative has-icon-left js_with_end_date hidden" style="min-width: 250px;margin-left: -1px;">
                                                                <input type="text"
                                                                       class="form-control form-control-sm pickadate"
                                                                       placeholder="{{ trans('locale.End date') }}"
                                                                       name="sale_to"
                                                                       data-value="{{ old('sale_to') }}"
                                                                       value="{{ old('sale_to') }}"
                                                                       style="border-bottom-left-radius: 0;border-top-left-radius: 0">
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
                                    @if($me->hasAccess(['coupons.write']))
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- promocode edit ends -->

    <div class="modal fade" id="js_categories_modal" tabindex="-1" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ trans('locale.Adding category') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body" id="js_categories_modal_content">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                        <i class="bx bx-x d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">{{ trans('locale.Cancel') }}</span>
                    </button>
                    <button type="button" class="btn btn-primary ml-1" id="js_add_checked_categories" data-dismiss="modal">
                        <i class="bx bx-check d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">{{ trans('locale.Add selected categories') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="js_products_modal" tabindex="-1" role="dialog" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-centered modal-dialog-scrollable modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ trans('locale.Adding products') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body" id="js_products_modal_content">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                        <i class="bx bx-x d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">{{ trans('locale.Cancel') }}</span>
                    </button>
                    <button type="button" class="btn btn-primary ml-1" id="js_add_checked_products" data-dismiss="modal">
                        <i class="bx bx-check d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">{{ trans('locale.Add selected products') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/repeater/jquery.repeater.js')}}"></script>
    <script src="{{asset('vendors/js/forms/spinner/jquery.bootstrap-touchspin.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.date.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/legacy.js')}}"></script>
    <script src="{{asset('vendors/js/ui/blockUI.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/bootstrap-treeview.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/admin/promocodes.js')}}"></script>
    @include('admin.media.assets')
@endsection
