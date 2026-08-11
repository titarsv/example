@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit product category'))
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
    <!-- category edit start -->
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
                            <a class="nav-link d-flex align-items-center btn-sm" id="seo-tab" data-toggle="tab"
                               href="#seo" aria-controls="seo" role="tab" aria-selected="false">
                                <i class="bx bx-globe mr-25"></i><span class="d-none d-sm-block">SEO</span>
                            </a>
                        </li>
                        <li class="custom-control custom-switch custom-switch-success">
                            <p class="mb-0 mr-1">{{ trans('locale.Active') }}</p>
                            <input type="checkbox" class="custom-control-input js_change_status" data-endpoint="products/categories"
                                   name="status" value="1" form="category_form" id="js_category_status"
                                   data-id="{{ $category->id }}" autocomplete="off"{{ $category->status ? ' checked' : '' }}>
                            <label class="custom-control-label" for="js_category_status">
                                <span class="switch-icon-left"><i class="bx bx-check"></i></span>
                                <span class="switch-icon-right"><i class="bx bx-x"></i></span>
                            </label>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- category edit Info form start -->
                            <form action="/admin/products/categories/edit/{{ $category->id }}" method="post" class="js_ajax_form" id="category_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Category name'),
                                                    'field' => [
                                                     'key' => 'name',
                                                     'item' => $category,
                                                     'required' => true
                                                    ]
                                                ])
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'select2',
                                                    'label' => trans('locale.Parent category'),
                                                    'field' => [
                                                      'key' => 'parent_id',
                                                      'options' => $categories,
                                                      'selected' => [old('parent_id') ? old('parent_id') : $category->parent_id]
                                                    ]
                                                ])
                                            </div>
                                            <div class="col-md-auto main-category-image">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'image',
                                                    'label' => trans('locale.Image'),
                                                    'field' => [
                                                      'key' => 'file_id',
                                                      'image' => $category->image
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <label>{{ trans('locale.Filters') }}</label>
                                                    @include('admin.layouts.form.select2', [
                                                     'key' => 'related_attribute_ids',
                                                     'options' => $attributes,
                                                     'multiple' => true,
                                                     'selected' => old('related_attribute_ids') ? old('related_attribute_ids') : $related_attributes
                                                   ])
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" name="allow_compare" value="1" id="allow_compare"{{ old('allow_compare', $category->allow_compare) ? ' checked' : '' }}>
                                                <label class="custom-control-label" for="allow_compare">{{ trans('locale.Allow comparing products in this category') }}</label>
                                                <div class="text-muted small mt-1">{{ trans('locale.Allow compare description') }}</div>
                                            </div>
                                        </div>
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'editor',
                                            'label' => trans('locale.Description'),
                                            'field' => [
                                              'key' => 'body',
                                              'item' => $category
                                            ]
                                        ])
                                    </div>
                                    @if($me->hasAccess(['categories.write']))
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
                            <!-- category edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="seo" aria-labelledby="seo-tab" role="tabpanel">
                            <!-- category edit SEO form start -->
                            <form action="/admin/products/categories/seo/{{ $category->id }}" method="post" class="js_ajax_form" novalidate>
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
                            <!-- category edit SEO form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- category edit ends -->

    @include('admin.layouts.mce', ['editors' => $editors])
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
    @include('admin.media.assets')
@endsection
