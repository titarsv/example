@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit Block'))
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
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h1 class="pages-title mb-0">{{ trans('locale.Edit Block') }}</h1>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-outline-primary btn-sm mr-1" onclick="copyShortcode()" title="{{ trans('locale.Copy shortcode') }}">
                <i class="bx bx-copy"></i>
            </button>
        </div>
    </div>
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
                            <form action="/admin/blocks/edit/{{ $block->id }}" method="post" class="js_ajax_form" id="block_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row">
                                    <div class="col-12">
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'string',
                                            'label' => trans('locale.Title'),
                                            'field' => [
                                             'key' => 'name',
                                             'item' => $block,
                                             'required' => true
                                            ]
                                        ])
                                        @include('admin.layouts.form.field-group', [
                                            'type' => 'select',
                                            'label' => trans('locale.Template'),
                                            'field' => [
                                              'key' => 'template',
                                              'options' => $templates,
                                              'selected' => [old('template') ? old('template') : $block->template]
                                            ]
                                        ])
                                        @if(empty(old('template', $block->template)))
                                            @include('admin.layouts.form.field-group', [
                                                'type' => 'editor',
                                                'label' => trans('locale.Content'),
                                                'field' => [
                                                  'key' => 'body',
                                                  'item' => $block
                                                ]
                                            ])
                                        @else
                                            @include('admin.blocks.fields')
                                        @endif
                                    </div>
                                    @if($me->hasAccess(['blocks.write']))
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
    <script src="{{asset('js/admin/blocks.js')}}"></script>

    <script>
        function copyShortcode() {
            const blockId = {{ $block->id }};
            const shortcode = `[block id="${blockId}"]`;
            
            // Create temporary textarea element
            const textarea = document.createElement('textarea');
            textarea.value = shortcode;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            try {
                // Copy to clipboard
                textarea.select();
                document.execCommand('copy');
                
                // Show success message
                if (typeof toastr !== 'undefined') {
                    toastr.success('{{ trans('locale.Shortcode copied to clipboard') }}: ' + shortcode);
                } else {
                    alert('{{ trans('locale.Shortcode copied') }}: ' + shortcode);
                }
            } catch (err) {
                // Fallback for older browsers
                if (typeof toastr !== 'undefined') {
                    toastr.error('{{ trans('locale.Failed to copy shortcode') }}');
                } else {
                    alert('{{ trans('locale.Failed to copy shortcode') }}');
                }
            } finally {
                // Remove temporary element
                document.body.removeChild(textarea);
            }
        }
    </script>

    @if(old('template') == 'public.layouts.blocks.main' || (empty(old('template')) && $block->template == 'public.layouts.blocks.main'))
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
