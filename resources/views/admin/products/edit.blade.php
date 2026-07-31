@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit product'))
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
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="attributes-tab" data-toggle="tab"
                               href="#attributes" aria-controls="attributes" role="tab" aria-selected="false">
                                <i class="bx bxs-spreadsheet mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Attributes') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="variations-tab" data-toggle="tab"
                               href="#variations" aria-controls="variations" role="tab" aria-selected="false">
                                <i class="bx bx-transfer mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Variations') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="relateds-tab" data-toggle="tab"
                               href="#relateds" aria-controls="relateds" role="tab" aria-selected="false">
                                <i class="bx bx-pin mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.relateds') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="video_reviews-tab" data-toggle="tab"
                               href="#video_reviews" aria-controls="video_reviews" role="tab" aria-selected="false">
                                <i class="bx bxs-videos mr-25"></i><span class="d-none d-sm-block">Video reviews</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="seo-tab" data-toggle="tab"
                               href="#seo" aria-controls="seo" role="tab" aria-selected="false">
                                <i class="bx bx-globe mr-25"></i><span class="d-none d-sm-block">SEO</span>
                            </a>
                        </li>
                        <li class="custom-control custom-switch custom-switch-success">
                            <p class="mb-0 mr-1">{{ trans('locale.Visibility') }}</p>
                            <input type="checkbox" class="custom-control-input js_change_status" data-endpoint="products"
                                   name="visible" value="1" form="product_form" id="js_product_status"
                                   data-id="{{ $product->id }}" autocomplete="off"{{ $product->visible ? ' checked' : '' }}>
                            <label class="custom-control-label" for="js_product_status">
                                <span class="switch-icon-left"><i class="bx bx-check"></i></span>
                                <span class="switch-icon-right"><i class="bx bx-x"></i></span>
                            </label>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- product edit Info form start -->
                            <form action="/admin/products/edit/{{ $product->id }}" method="post" class="js_ajax_form" id="product_form" novalidate>
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
                                                     'item' => $product,
                                                     'required' => true
                                                    ]
                                                ])
                                                <div class="field-group">
                                                    <div class="row">
                                                        <div class="col-sm-3">
                                                            <label>{{ trans('locale.Base price') }}</label>
                                                            @include('admin.layouts.form.string', [
                                                             'key' => 'original_price',
                                                             'item' => $product
                                                            ])
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Sale price') }}</label>
                                                            <fieldset>
                                                                <div class="input-group input-group-sm">
                                                                    <div class="input-group-prepend">
                                                                        <div class="input-group-text" style="padding: 0 5px 8px;">
                                                                            <div class="checkbox checkbox-sm">
                                                                                <input type="checkbox" class="checkbox__input " id="checkboxinput" value="1" name="sale"{{ old('sale', $product->sale) ? ' checked' : '' }}>
                                                                                <label for="checkboxinput"></label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <input type="text" aria-label="{{ trans('locale.Discounted price') }}" class="form-control form-control-sm" placeholder="{{ trans('locale.Discounted price') }}" name="sale_price" value="{{ old('sale_price') ? old('sale_price') : $product->sale_price }}">
                                                                    <div class="input-group-append">
                                                                        <div class="position-relative has-icon-left" style="min-width: 250px;margin-left: -1px;">
                                                                            <input type="text"
                                                                                   class="form-control form-control-sm pickadate"
                                                                                   placeholder="{{ trans('locale.Start date') }}"
                                                                                   name="sale_from"
                                                                                   data-value="{{ old('sale_from', !empty($product->sale_from) ? date('d.m.Y', strtotime($product->sale_from)) : null) }}"
                                                                                   value="{{ old('sale_from', !empty($product->sale_from) ? date('d.m.Y', strtotime($product->sale_from)) : null) }}"
                                                                                   style="border-radius: 0">
                                                                            <div class="form-control-position">
                                                                                <i class='bx bx-calendar' style="margin-top: 9px;"></i>
                                                                            </div>
                                                                        </div>
                                                                        <div class="position-relative has-icon-left" style="min-width: 250px;margin-left: -1px;">
                                                                            <input type="text"
                                                                                   class="form-control form-control-sm pickadate"
                                                                                   placeholder="{{ trans('locale.End date') }}"
                                                                                   name="sale_to"
                                                                                   data-value="{{ old('sale_to', !empty($product->sale_to) ? date('d.m.Y', strtotime($product->sale_to)) : null) }}"
                                                                                   value="{{ old('sale_to', !empty($product->sale_to) ? date('d.m.Y', strtotime($product->sale_to)) : null) }}"
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
                                                <div class="field-group">
                                                    <div class="row">
                                                        <div class="col">
                                                            <label>{{ trans('locale.SKU') }}</label>
                                                            @include('admin.layouts.form.string', [
                                                             'key' => 'sku',
                                                             'item' => $product
                                                            ])
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Availability') }}</label>
                                                            @include('admin.layouts.form.select', [
                                                             'key' => 'stock',
                                                             'options' => [
                                                                 (object)['value' => '1', 'name' => trans('locale.In stock')],
                                                                 (object)['value' => '-2', 'name' => trans('locale.Out of stock')],
                                                                 (object)['value' => '0', 'name' => trans('locale.Expected')],
                                                                 (object)['value' => '-1', 'name' => trans('locale.On order')]
                                                             ],
                                                             'selected' => [old('stock', $product->stock)]
                                                            ])
{{--                                                            @include('admin.layouts.form.string', [--}}
{{--                                                             'key' => 'stock',--}}
{{--                                                             'item' => $product,--}}
{{--                                                             'required' => true,--}}
{{--                                                             'languages' => null--}}
{{--                                                            ])--}}
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Sort priority') }}</label>
                                                            @include('admin.layouts.form.string', [
                                                             'key' => 'sort_priority',
                                                             'item' => $product
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
                                                                  'selected' => $added_categories
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
                                                             'key' => 'file_id',
                                                             'image' => $product->image
                                                            ])
                                                        </div>
                                                        <div class="col">
                                                            <label>{{ trans('locale.Gallery') }}</label>
                                                            @include('admin.layouts.form.gallery', [
                                                             'key' => 'gallery',
                                                             'gallery' => $product->gallery
                                                            ])
                                                        </div>
                                                    </div>
                                                </div>
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'editor',
                                                    'label' => trans('locale.Product description'),
                                                    'field' => [
                                                     'key' => 'description',
                                                     'item' => $product
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['products.write']))
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
                            <!-- product edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="attributes" aria-labelledby="attributes-tab" role="tabpanel">
                            <!-- product edit attributes form start -->
                            <form action="/admin/products/sync_attributes/{{ $product->id }}" method="post" class="js_ajax_form{{ empty($product_attributes) ? ' empty' : '' }} attributes-repeater" novalidate>
                                {!! csrf_field() !!}
                                <div data-repeater-list="attributes">
                                    @if(!empty($product_attributes))
                                        @foreach($product_attributes as $key => $attr)
                                            @include('admin.products.attributes.attribute', ['key' => $key, 'attr' => $attr])
                                        @endforeach
                                    @else
                                        @include('admin.products.attributes.attribute', ['key' => 0, 'attr' => null])
                                    @endif
                                </div>
                                @if($me->hasAccess(['products.write']))
                                    <div class="row mt-1">
                                        <div class="col-6">
                                            <button class="btn btn-primary" data-repeater-create type="button"><i class="bx bx-plus"></i>
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
                                @endif
                            </form>
                            <!-- product edit attributes form ends -->
                        </div>
                        <div class="tab-pane fade show" id="variations" aria-labelledby="variations-tab" role="tabpanel">
                            <!-- product edit variations form start -->
                            <form action="/admin/products/variations/{{ $product->id }}" method="post" class="variations-repeater{{ $product->variations->count() ? '' : ' empty' }} js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div data-repeater-list="variations">
                                    @if($product->variations->count())
                                        @foreach($product->variations as $i => $variation)
                                            @include('admin.products.variations.variation', ['index' => $i, 'variation' => $variation])
                                        @endforeach
                                    @else
                                        @include('admin.products.variations.variation', ['index' => 0, 'variation' => null])
                                    @endif
                                </div>
                                @if($me->hasAccess(['products.write']))
                                    <div class="row mt-5">
                                        <div class="col-6">
                                            <button class="btn btn-primary" data-repeater-create type="button"><i class="bx bx-plus"></i>
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
                                @endif
                            </form>
                            <!-- product edit variations form ends -->
                        </div>
                        <div class="tab-pane fade show" id="video_reviews" aria-labelledby="video_reviews-tab" role="tabpanel">
                            <!-- product edit video_reviews form start -->
                            <form action="/admin/products/video_reviews/{{ $product->id }}" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col">
                                            <label>{{ trans('locale.Video reviews') }}</label>
                                            @include('admin.layouts.form.gallery', [
                                             'key' => 'video_reviews',
                                             'gallery' => $product->video_reviews,
                                             'extensions' => 'video'
                                            ])
                                        </div>
                                    </div>
                                </div>
                                @if($me->hasAccess(['products.write']))
                                    <div class="row mt-5">
                                        <div class="col d-flex flex-sm-row flex-column justify-content-end">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    </div>
                                @endif
                            </form>
                            <!-- product edit video_reviews form ends -->
                        </div>
                        <div class="tab-pane fade show" id="relateds" aria-labelledby="relateds-tab" role="tabpanel">
                            <!-- product edit relateds form start -->
                            <form action="/admin/products/related/{{ $product->id }}" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            @include('admin.layouts.form.field-group', [
                                                'type' => 'select2',
                                                'label' => trans('locale.related_products'),
                                                'field' => [
                                                  'key' => 'related',
                                                  'multiple' => true,
                                                  'required' => false,
                                                  'options' => !empty($sets) ? $sets : [],
                                                  'selected' => (array)old('related', $related)
                                                ]
                                            ])
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            @include('admin.layouts.form.field-group', [
                                                'type' => 'select2',
                                                'label' => trans('locale.similar_products'),
                                                'field' => [
                                                  'key' => 'similar',
                                                  'multiple' => true,
                                                  'required' => false,
                                                  'options' => !empty($sets) ? $sets : [],
                                                  'selected' => (array)old('similar', $similar)
                                                ]
                                            ])
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['products.write']))
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
                            <!-- product edit relateds form ends -->
                        </div>
                        <div class="tab-pane fade show" id="seo" aria-labelledby="seo-tab" role="tabpanel">
                            <!-- product edit SEO form start -->
                            <form action="/admin/products/seo/{{ $product->id }}" method="post" class="js_ajax_form" novalidate>
                                {!! csrf_field() !!}
                                @include('admin.layouts.seo')
                                @if($me->hasAccess(['seo.write']))
                                    <div class="row">
                                        <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    </div>
                                @endif
                            </form>
                            <!-- product edit SEO form ends -->
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
