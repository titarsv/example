@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit Article'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/pages/page-blog.css')}}">
@endsection

@section('content')
    <!-- articl edit start -->
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
                                <i class="bx bx-globe mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.SEO') }}</span>
                            </a>
                        </li>
                        <li class="custom-control custom-switch custom-switch-success">
                            <p class="mb-0 mr-1">{{ trans('locale.Active') }}</p>
                            <input type="checkbox" class="custom-control-input js_change_status" data-endpoint="articles"
                                   name="status" value="1" form="article_form" id="js_article_status"
                                   data-id="{{ $article->id }}" autocomplete="off"{{ $article->status ? ' checked' : '' }}>
                            <label class="custom-control-label" for="js_article_status">
                                <span class="switch-icon-left"><i class="bx bx-check"></i></span>
                                <span class="switch-icon-right"><i class="bx bx-x"></i></span>
                            </label>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- article edit Info form start -->
                            <form action="/admin/articles/edit/{{ $article->id }}" method="post" class="js_ajax_form" id="article_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Title'),
                                                    'field' => [
                                                     'key' => 'name',
                                                     'item' => $article,
                                                     'required' => true
                                                    ]
                                                ])
                                                {{--@include('admin.layouts.form.field-group', [--}}
                                                    {{--'type' => 'select',--}}
                                                    {{--'label' => trans('locale.Category'),--}}
                                                    {{--'field' => [--}}
                                                     {{--'key' => 'category_id',--}}
                                                     {{--'options' => $categories,--}}
                                                     {{--'selected' => [old('category_id') ? old('category_id') : $article->category_id],--}}
                                                     {{--'required' => true--}}
                                                    {{--]--}}
                                                {{--])--}}
                                                <div class="row">
                                                    <div class="col">
                                                        @include('admin.layouts.form.field-group', [
                                                            'type' => 'select2',
                                                            'label' => trans('locale.Categories'),
                                                            'languages' => false,
                                                            'field' => [
                                                                'key' => 'category_id',
                                                                'multiple' => true,
                                                                'required' => true,
                                                                'options' => $categories,
                                                                'selected' => $added_categories
                                                            ]
                                                        ])
                                                    </div>
                                                    <div class="col">
                                                        @include('admin.layouts.form.field-group', [
                                                            'type' => 'select',
                                                            'label' => trans('locale.Author'),
                                                            'languages' => false,
                                                            'field' => [
                                                                'key' => 'user_id',
                                                                'required' => true,
                                                                'options' => $authors,
                                                                'selected' => [$article->user_id]
                                                            ]
                                                        ])
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col">
                                                        @include('admin.layouts.form.field-group', [
                                                            'type' => 'date_time',
                                                            'label' => trans('locale.Publication date'),
                                                            'languages' => false,
                                                            'field' => [
                                                             'key' => 'created_at',
                                                             'item' => $article,
                                                             'required' => true
                                                            ]
                                                        ])
                                                    </div>
                                                    <div class="col">
                                                        @include('admin.layouts.form.field-group', [
                                                            'type' => 'string',
                                                            'label' => trans('locale.Reading time (minutes)'),
                                                            'languages' => false,
                                                            'field' => [
                                                             'key' => 'reading_time',
                                                             'item' => $article,
                                                             'required' => true
                                                            ]
                                                        ])
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-auto main-article-image">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'image',
                                                    'label' => trans('locale.Image'),
                                                    'field' => [
                                                      'key' => 'image_id',
                                                      'image' => $article->image
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'editor',
                                            'label' => trans('locale.Content'),
                                            'field' => [
                                              'key' => 'body',
                                              'item' => $article
                                            ]
                                        ])
                                    </div>
                                    @if($me->hasAccess(['articles.write']))
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
                            <!-- article edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="seo" aria-labelledby="seo-tab" role="tabpanel">
                            <!-- article edit SEO form start -->
                            <form action="/admin/articles/seo/{{ $article->id }}" method="post" class="js_ajax_form" novalidate>
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
                            <!-- article edit SEO form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- articl edit ends -->

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
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/select/form-select2.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/scripts/popover/popover.js')}}"></script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    @include('admin.media.assets')
@endsection
