@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.SEO Settings'))
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
    <h1 class="pages-title">{{ trans('locale.Settings') }}</h1>
    <!-- seo settings edit start -->
    <section class="users-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="google-tab" data-toggle="tab"
                               href="#google" aria-controls="google" role="tab" aria-selected="false">
                                <i class="bx bxl-google mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Google') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="fb-tab" data-toggle="tab"
                               href="#fb" aria-controls="fb" role="tab" aria-selected="false">
                                <i class="bx bxl-facebook mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Facebook Pixel') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="microdata-tab" data-toggle="tab"
                               href="#microdata" aria-controls="microdata" role="tab" aria-selected="false">
                                <i class="bx bx-globe mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Structured Data') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="templates-tab" data-toggle="tab"
                               href="#templates" aria-controls="templates" role="tab" aria-selected="false">
                                <i class="bx bx-file mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Templates') }}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="google" aria-labelledby="google-tab" role="tabpanel">
                            <!-- seo settings edit google form start -->
                            <form action="/admin/promotion/google" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'text',
                                                    'label' => trans('locale.GTM Base Code'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'google',
                                                     'item' => $settings,
                                                     'required' => true
                                                    ]
                                                ])
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'text',
                                                    'label' => trans('locale.GTM Noscript Code'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'google_noscript',
                                                     'item' => $settings,
                                                     'required' => true
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <div class="field-group">
                                                    <div class="row">
                                                        <div class="col">
                                                            <label>{{ trans('locale.Ecommerce Google Analytics') }}</label>
                                                            <div class="custom-control custom-switch d-flex align-items-center" style="height: 31px;">
                                                                <span>{{ trans('locale.Disable') }}</span>
                                                                <input autocomplete="off" type="checkbox" class="custom-control-input" name="ega" value="1" id="ega"{{ old('ega', !empty($settings->ega)) ? ' checked' : '' }}>
                                                                <label class="custom-control-label ml-1 mr-1" for="ega">
                                                                </label>
                                                                <span>{{ trans('locale.Enable') }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Ecommerce Google Adwords') }}</label>
                                                            <div class="custom-control custom-switch d-flex align-items-center" style="height: 31px;">
                                                                <span>{{ trans('locale.Disable') }}</span>
                                                                <input autocomplete="off" type="checkbox" class="custom-control-input" name="ads" value="1" id="ads"{{ old('ads', !empty($settings->ads)) ? ' checked' : '' }}>
                                                                <label class="custom-control-label ml-1 mr-1" for="ads">
                                                                </label>
                                                                <span>{{ trans('locale.Enable') }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            @include('admin.layouts.form.field-group', [
                                                                'type' => 'string',
                                                                'label' => trans('locale.Google conversion ID'),
                                                                'languages' => null,
                                                                'field' => [
                                                                 'key' => 'google_conversion_id',
                                                                 'item' => $settings
                                                                ]
                                                            ])
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['seo.write']))
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
                            <!-- seo settings edit google form ends -->
                        </div>
                        <div class="tab-pane fade show" id="fb" aria-labelledby="fb-tab" role="tabpanel">
                            <!-- seo settings edit FB form start -->
                            <form action="/admin/promotion/fb" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'text',
                                                    'label' => trans('locale.Facebook Pixel Code'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'fb_pixel',
                                                     'item' => $settings
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['seo.write']))
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
                            <!-- seo settings edit FB form ends -->
                        </div>
                        <div class="tab-pane fade show" id="microdata" aria-labelledby="microdata-tab" role="tabpanel">
                            <!-- seo settings edit Microdata form start -->
                            <form action="/admin/promotion/microdata" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'select2',
                                                    'label' => trans('locale.Type'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_type',
                                                     'item' => $settings,
                                                     'options' => $ld_types,
                                                     'selected' => [old('ld_type') ? old('ld_type') : $settings->ld_type],
                                                     'required' => true
                                                    ]
                                                ])
                                            </div>
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Organization Name'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_name',
                                                     'item' => $settings,
                                                     'required' => true
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'text',
                                                    'label' => trans('locale.Description'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_description',
                                                     'item' => $settings,
                                                     'required' => true
                                                    ]
                                                ])
                                            </div>
                                            <div class="col-md-auto main-category-image">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'image',
                                                    'label' => trans('locale.Image'),
                                                    'field' => [
                                                      'key' => 'ld_image',
                                                      'image' => $image
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Region'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_region',
                                                     'item' => $settings
                                                    ]
                                                ])
                                            </div>
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.City'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_city',
                                                     'item' => $settings
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Street, Building'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_street',
                                                     'item' => $settings
                                                    ]
                                                ])
                                            </div>
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Postal Code'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_postcode',
                                                     'item' => $settings
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Main Phone'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_phone',
                                                     'item' => $settings
                                                    ]
                                                ])
                                            </div>
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'select2',
                                                    'label' => trans('locale.Payment Methods'),
                                                    'languages' => null,
                                                    'field' => [
                                                     'key' => 'ld_payments',
                                                     'multiple' => true,
                                                     'item' => $settings,
                                                     'options' => $ld_payments,
                                                     'selected' => [old('ld_payments') ? old('ld_payments') : $settings->ld_payments]
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            @foreach([
                                                'Mo' => trans('locale.Monday'),
                                                'Tu' => trans('locale.Tuesday'),
                                                'We' => trans('locale.Wednesday'),
                                                'Th' => trans('locale.Thursday'),
                                                'Fr' => trans('locale.Friday'),
                                                'Sa' => trans('locale.Saturday'),
                                                'Su' => trans('locale.Sunday')
                                            ] as $id => $name)
                                                <div class="col-sm-4">
                                                    <fieldset>
                                                        <div class="input-group input-group-sm" style="flex-wrap: nowrap; margin-bottom: 15px;">
                                                            <div class="input-group-prepend">
                                                                <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                    <div class="checkbox checkbox-sm">
                                                                        <input autocomplete="off" type="checkbox" class="checkbox__input" id="ld_{{ $id }}" name="ld_opening_hours[{{ $id }}][trigger]"{{ !empty($settings->ld_opening_hours->$id->trigger) ? ' checked' : '' }}>
                                                                        <label for="ld_{{ $id }}"></label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="input-group-append">
                                                                <span class="input-group-text">{{ $name }}</span>
                                                            </div>
                                                            <div class="input-group-append">
                                                                <div class="position-relative has-icon-left" style="margin-left: -1px;">
                                                                    <input autocomplete="off"
                                                                           type="text"
                                                                           class="form-control form-control-sm pickatime-format"
                                                                           placeholder="{{ trans('locale.From') }}"
                                                                           name="ld_opening_hours[{{ $id }}][from]"
                                                                           id="ld_opening_hours_{{ $id }}_from"
                                                                           value="{{ !empty($settings->ld_opening_hours->$id->from) ? $settings->ld_opening_hours->$id->from : '' }}"
                                                                           style="border-radius: 0">
                                                                    <div class="form-control-position">
                                                                        <i class='bx bx-history' style="margin-top: 9px;"></i>
                                                                    </div>
                                                                </div>
                                                                <div class="position-relative has-icon-left" style="margin-left: -1px;">
                                                                    <input autocomplete="off"
                                                                           type="text"
                                                                           class="form-control form-control-sm pickatime-format"
                                                                           placeholder="{{ trans('locale.To') }}"
                                                                           name="ld_opening_hours[{{ $id }}][to]"
                                                                           id="ld_opening_hours_{{ $id }}_to"
                                                                           value="{{ !empty($settings->ld_opening_hours->$id->to) ? $settings->ld_opening_hours->$id->to : '' }}"
                                                                           style="border-bottom-left-radius: 0;border-top-left-radius: 0">
                                                                    <div class="form-control-position">
                                                                        <i class='bx bx-history' style="margin-top: 9px;"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>
                                            @endforeach
                                            <div class="col-sm-4">
                                                @include('admin.layouts.form.string', [
                                                 'placeholder' => trans('locale.Latitude'),
                                                 'key' => 'ld_latitude',
                                                 'name' => 'ld_latitude',
                                                 'item' => $settings
                                                ])
                                            </div>
                                            <div class="col-sm-4">
                                                @include('admin.layouts.form.string', [
                                                 'placeholder' => trans('locale.Longitude'),
                                                 'key' => 'ld_longitude',
                                                 'name' => 'ld_longitude',
                                                 'item' => $settings
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <div class="field-group">
                                                    <label>{{ trans('locale.Social Networks') }}</label>
                                                    <div class="form-group">
                                                        <input type="text" class="form-control form-control-sm" name="social[0]" value="{{ old('social[0]', isset($settings->social[0]) ? $settings->social[0] : '') }}" autocomplete="off" aria-invalid="false">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="field-group">
                                                    <label></label>
                                                    <div class="form-group">
                                                        <input type="text" class="form-control form-control-sm" name="social[1]" value="{{ old('social[1]', isset($settings->social[1]) ? $settings->social[1] : '') }}" autocomplete="off" aria-invalid="false">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="field-group">
                                                    <label></label>
                                                    <div class="form-group">
                                                        <input type="text" class="form-control form-control-sm" name="social[2]" value="{{ old('social[2]', isset($settings->social[2]) ? $settings->social[2] : '') }}" autocomplete="off" aria-invalid="false">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <div class="field-group">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control form-control-sm" name="social[3]" value="{{ old('social[3]', isset($settings->social[3]) ? $settings->social[3] : '') }}" autocomplete="off" aria-invalid="false">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="field-group">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control form-control-sm" name="social[4]" value="{{ old('social[4]', isset($settings->social[4]) ? $settings->social[4] : '') }}" autocomplete="off" aria-invalid="false">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="field-group">
                                                    <div class="form-group">
                                                        <input type="text" class="form-control form-control-sm" name="social[5]" value="{{ old('social[5]', isset($settings->social[5]) ? $settings->social[5] : '') }}" autocomplete="off" aria-invalid="false">
                                                        <div class="help-block"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['seo.write']))
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
                            <!-- seo settings edit Microdata form ends -->
                        </div>
                        <div class="tab-pane fade show" id="templates" aria-labelledby="templates-tab" role="tabpanel">
                            <form action="/admin/promotion/templates" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <div class="row">
                                                    <div class="col-sm-6">

                                                    </div>
                                                    <div class="col-sm-6 text-right">
                                                        @if(!empty($locales_names) && count($locales_names) > 1)
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                                                    <img src="/images/larchik/flags/{{$main_lang}}.png" alt="{{$locales_names[$main_lang]}}" style="width: 16px; height: 12px; margin-right: 5px;"> {{$locales_names[$main_lang]}}
                                                                </button>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <a class="dropdown-item js_template_lang_switcher" href="javascript:void(0)" data-lang="{{$lang}}">
                                                                            <img src="/images/larchik/flags/{{$lang}}.png" alt="{{$lang_name}}" style="width: 16px; height: 12px; margin-right: 5px;"> {{$lang_name}}
                                                                        </a>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Product Variables') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <p><b>[product_name]</b> - {{ trans('locale.product name') }}</p>
                                                                    <p><b>[product_h1]</b> - {{ trans('locale.product H1') }}</p>
                                                                    <p><b>[product_brand]</b> - {{ trans('locale.product brand') }}</p>
                                                                    <p><b>[product_color]</b> - {{ trans('locale.product color') }}</p>
                                                                    <p><b>[product_category]</b> - {{ trans('locale.product category') }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Product Title') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="products_meta_title_{{ $lang }}" value="{{ isset($settings->{'products_meta_title_'.$lang}) ? $settings->{'products_meta_title_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="products_meta_title{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'products_meta_title'.(isset($locale) ? '_products_meta_title' : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Product Description') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="products_meta_description_{{ $lang }}" value="{{ isset($settings->{'products_meta_description_'.$lang}) ? $settings->{'products_meta_description_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="products_meta_description{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'products_meta_description'.(isset($locale) ? '_products_meta_description' : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Product Keywords') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="products_meta_keywords_{{ $lang }}" value="{{ isset($settings->{'products_meta_keywords_'.$lang}) ? $settings->{'products_meta_keywords_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="products_meta_keywords{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'products_meta_keywords'.(isset($locale) ? '_products_meta_keywords' : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Category Variables') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <p><b>[category_name]</b> - {{ trans('locale.category name') }}</p>
                                                                    <p><b>[parent_category_name]</b> - {{ trans('locale.parent category name') }}</p>
                                                                    <p><b>[category_geo_city]</b> - {{ trans('locale.city name') }}</p>
                                                                    <p><b>[page_number]</b> - {{ trans('locale.current page number (empty for first page)') }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Category Title') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="categories_meta_title_{{ $lang }}" value="{{ isset($settings->{'categories_meta_title_'.$lang}) ? $settings->{'categories_meta_title_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="categories_meta_title{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'categories_meta_title'.(isset($locale) ? '_categories_meta_title' : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Category Description') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="categories_meta_description_{{ $lang }}" value="{{ isset($settings->{'categories_meta_description_'.$lang}) ? $settings->{'categories_meta_description_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="categories_meta_description{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'categories_meta_description'.(isset($locale) ? '_categories_meta_description' : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Category Keywords') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="categories_meta_keywords_{{ $lang }}" value="{{ isset($settings->{'categories_meta_keywords_'.$lang}) ? $settings->{'categories_meta_keywords_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="categories_meta_keywords{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'categories_meta_keywords'.(isset($locale) ? '_categories_meta_keywords' : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Filter Variables') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row">
                                                                <div class="col">
                                                                    <p><b>[category_name]</b> - {{ trans('locale.category name') }}</p>
                                                                    <p><b>[parent_category_name]</b> - {{ trans('locale.parent category name') }}</p>
                                                                    <p><b>[category_geo_city]</b> - {{ trans('locale.city name') }}</p>
                                                                    <p><b>[attribute_names]</b> - {{ trans('locale.attribute names') }}</p>
                                                                    <p><b>[attribute_values]</b> - {{ trans('locale.attribute values') }}</p>
                                                                    <p><b>[page_number]</b> - {{ trans('locale.current page number (empty for first page)') }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Filter Title') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="filters_meta_title_{{ $lang }}" value="{{ isset($settings->{'filters_meta_title_'.$lang}) ? $settings->{'filters_meta_title_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="filters_meta_title{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'filters_meta_title'.(isset($locale) ? '_'.$locale : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Filter H1') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="filters_meta_h1_{{ $lang }}" value="{{ isset($settings->{'filters_meta_h1_'.$lang}) ? $settings->{'filters_meta_h1_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="filters_meta_h1{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'filters_meta_h1'.(isset($locale) ? '_'.$locale : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Filter Description') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="filters_meta_description_{{ $lang }}" value="{{ isset($settings->{'filters_meta_description_'.$lang}) ? $settings->{'filters_meta_description_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="filters_meta_description{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'filters_meta_description'.(isset($locale) ? '_'.$locale : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <div class="row">
                                                        <label class="col-sm-2 text-right">{{ trans('locale.Filter Keywords') }}</label>
                                                        <div class="form-element col-sm-10">
                                                            <div class="row js_langs">
                                                                @if(!empty($locales_names) && count($locales_names) > 1)
                                                                    @foreach($locales_names as $lang => $lang_name)
                                                                        <div class="col js_lang lng_{{$lang}}{{$lang == $main_lang ? ' active_lang' : ''}}">
                                                                            <input type="text" class="form-control" name="filters_meta_keywords_{{ $lang }}" value="{{ isset($settings->{'filters_meta_keywords_'.$lang}) ? $settings->{'filters_meta_keywords_'.$lang} : '' }}" placeholder="{{$lang_name}}">
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <div class="col js_lang lng_{{$locale}}{{$locale == $main_lang ? ' active_lang' : ''}}">
                                                                        <input type="text" class="form-control" name="filters_meta_keywords{{ isset($locale) ? '_'.$locale : '' }}" value="{{ $settings->{'filters_meta_keywords'.(isset($locale) ? '_'.$locale : '')} }}">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['seo.write']))
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
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- seo settings edit ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.js')}}"></script>
    <script src="{{asset('vendors/js/pickers/pickadate/picker.time.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/seo.js')}}"></script>
    @include('admin.media.assets')
    <script>
    $(document).ready(function() {
        // Language switcher for templates tab
        $('.js_template_lang_switcher').click(function(e) {
            e.preventDefault();

            var lang = $(this).data('lang');
            var langName = $(this).text().trim();
            var flagSrc = $(this).find('img').attr('src');

            // Update dropdown button
            $('#templates .dropdown-toggle').html('<img src="' + flagSrc + '" alt="' + langName + '" style="width: 16px; height: 12px;"> ' + langName);

            // Hide all language fields
            $('#templates .js_lang').removeClass('active_lang').hide();

            // Show selected language fields
            $('#templates .js_lang.lng_' + lang).addClass('active_lang').show();
        });

        // Initially show only main language fields
        $('#templates .js_lang').removeClass('active_lang').hide();
        $('#templates .js_lang.lng_{{$main_lang}}').addClass('active_lang').show();
    });
    </script>
@endsection
