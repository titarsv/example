@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit page'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/pages/page-pages.css')}}">
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
                            <input type="checkbox" class="custom-control-input js_change_status" data-endpoint="pages"
                                   name="status" value="1" form="page_form" id="js_page_status"
                                   data-id="{{ $page->id }}" autocomplete="off"{{ $page->status ? ' checked' : '' }}>
                            <label class="custom-control-label" for="js_page_status">
                                <span class="switch-icon-left"><i class="bx bx-check"></i></span>
                                <span class="switch-icon-right"><i class="bx bx-x"></i></span>
                            </label>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="information" aria-labelledby="information-tab" role="tabpanel">
                            <!-- page edit Info form start -->
                            <form action="/admin/pages/edit/{{ $page->id }}" method="post" class="js_ajax_form" id="page_form" novalidate>
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
                                                     'item' => $page,
                                                     'required' => true
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'select',
                                            'label' => trans('locale.Parent page'),
                                            'field' => [
                                              'key' => 'parent_id',
                                              'options' => $pages,
                                              'selected' => [old('parent_id') ? old('parent_id') : $page->parent_id]
                                            ]
                                        ])
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'select',
                                            'label' => trans('locale.Template'),
                                            'field' => [
                                              'key' => 'template',
                                              'options' => $templates,
                                              'selected' => [old('template') ? old('template') : $page->template]
                                            ]
                                        ])
                                        @if(old('template') == 'public.page' || (empty(old('template')) && $page->template == 'public.page'))
                                            @include('admin.layouts.form.field-group', [
                                                'type' => 'editor',
                                                'label' => trans('locale.Content'),
                                                'field' => [
                                                  'key' => 'body',
                                                  'item' => $page
                                                ]
                                            ])
                                        @else
                                            @include('admin.pages.fields')
                                        @endif
                                    </div>
                                    @if($me->hasAccess(['pages.write']))
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
                            <!-- page edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="seo" aria-labelledby="seo-tab" role="tabpanel">
                            <!-- page edit SEO form start -->
                            <form action="/admin/pages/seo/{{ $page->id }}" method="post" class="js_ajax_form" novalidate>
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
                            <!-- page edit SEO form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- articl edit ends -->
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
    <script src="{{asset('js/admin/pages.js')}}"></script>

    @if(old('template') == 'public.layouts.pages.main' || (empty(old('template')) && $page->template == 'public.layouts.pages.main'))
        @include('admin.layouts.mce', ['editors' => $editors])
    @else
        @php
            if($fields){
                foreach($fields as $lang => $fields_lang){
                    foreach($fields_lang as $field){
                        if($field->type == 'wysiwyg'){
                            if(!empty($field->langs)){
                                $editors[] = 'fields'.$lang.$field->slug;
                            }else{
                                $editors[] = 'fields'.'all'.$field->slug;
                            }
                        }elseif($field->type == 'repeater'){
                            $editors = array_merge($editors, Helper::getChildrenEditors($field->fields, $field->slug, $field->data, $lang));
                        }
                    }
                }
                $editors = array_unique($editors);
            }
        @endphp
        @include('admin.layouts.mce', ['editors' => $editors])
    @endif
    @include('admin.media.assets')
@endsection
