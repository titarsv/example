@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Block Template'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/dragula.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/bootstrap-treeview.min.css')}}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/foldgutter.min.css" rel="stylesheet" />
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/pages-templates.css')}}">
@endsection

@section('content')
    <h1 class="pages-title">{{ trans('locale.Block Template') }} "{{ $template->name }}.blade.php"</h1>
    <div class="hidden">
        @include('admin.pages.templates.field', ['index' => 0, 'key' => 0, 'field' => null, 'parent_key' => '_0', 'parent' => '', 'parent_id' => 'basic-list-group'])
        @include('admin.pages.templates.fields.select', ['field' => null, 'parent_key' => '_0', 'parent' => '', 'parent_id' => 'basic-list-group'])
        @include('admin.pages.templates.fields.number', ['field' => null, 'parent_key' => '_0', 'parent' => '', 'parent_id' => 'basic-list-group'])
        @include('admin.pages.templates.fields.repeater', ['field' => null, 'parent_key' => '_0', 'parent' => '', 'parent_id' => 'basic-list-group'])
        @include('admin.pages.templates.fields.group', ['field' => null, 'parent_key' => '_0', 'parent' => '', 'parent_id' => 'basic-list-group'])
    </div>
    <section class="users-edit">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="fields-tab" data-toggle="tab"
                               href="#fields" aria-controls="fields" role="tab" aria-selected="false">
                                <i class="bx bxs-spreadsheet mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Fields') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="template-tab" data-toggle="tab"
                               href="#template" aria-controls="template" role="tab" aria-selected="false">
                                <i class="bx bxs-file-html mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Template') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="preview-tab" data-toggle="tab"
                               href="#preview" aria-controls="preview" role="tab" aria-selected="false">
                                <i class="bx bx-show mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Preview') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="history-tab" data-toggle="tab"
                               href="#history" aria-controls="history" role="tab" aria-selected="false">
                                <i class="bx bx-history mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.History') }}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active fade show" id="fields" aria-labelledby="fields-tab" role="tabpanel">
                            <form action="/admin/pages/template/fields/{{ $template->name }}" method="post" class="js_ajax_form" id="js_pages_template_form" novalidate>
                                {!! csrf_field() !!}
                                <div class="row mb-2">
                                    <div class="col-md-4">
                                        <fieldset>
                                            <label for="helperText">{{ trans('locale.Category') }}</label>
                                            <input type="text" class="form-control" name="category" value="{{ !empty($template->category) ? $template->category : '' }}">
                                        </fieldset>
                                    </div>
                                </div>
                                <div id="accordion-icon-wrapper" class="collapse-icon accordion-icon-rotate">
                                    <div class="accordion fields" data-parent="" id="basic-list-group">
                                        @foreach($template->fields as $key => $field)
                                            @include('admin.pages.templates.field', ['index' => $key + 1, 'key' => $key + 1, 'parent_key' => '', 'parent' => "fields[".($key + 1)."]", 'parent_id' => 'basic-list-group'])
                                        @endforeach
                                    </div>
                                </div>
                                @if($me->hasAccess(['pages.write']))
                                    <div class="row mt-5">
                                        <div class="col-6">
                                            <button class="btn btn-primary" id="add_field" data-key="{{ count($template->fields) + 1 }}" type="button"><i class="bx bx-plus"></i>
                                                {{ trans('locale.Add field') }}
                                            </button>
                                        </div>
                                        <div class="col-6 d-flex flex-sm-row flex-column justify-content-end">
                                            <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true"></span>
                                                {{ trans('locale.Save changes') }}
                                            </button>
                                            <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                        </div>
                                    </div>
                                @endif
                            </form>
                        </div>
                        <div class="tab-pane fade show email-application" id="template" aria-labelledby="template-tab" role="tabpanel">
                            <div class="content-area-wrapper">
                                <div class="sidebar-left">
                                    <div class="sidebar">
                                        <div class="sidebar-content email-app-sidebar d-flex">
                                            <div class="email-app-menu">
                                                <div class="form-group form-group-compose">
                                                    <button type="button" id="generate_template" class="btn btn-primary btn-block mt-1 compose-btn">
                                                        <i class="bx bx-reset"></i>
                                                        {{ trans('locale.Generate') }}
                                                    </button>
                                                </div>
                                                <div class="sidebar-menu-list">
                                                    <div id="treeview"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="content-right">
                                    <div class="app-content-overlay"></div>
                                    <div class="email-app-area">
                                        <div class="email-app-list-wrapper">
                                            <div class="email-app-list">
                                                <div class="email-action">
                                                    <div class="action-left d-flex align-items-center">
                                                        <i class="bx bx-info-circle"></i>
                                                    </div>
                                                    <pre><code id="code_wrapper"></code></pre>
                                                    <button class="btn btn-icon btn-success" id="insert_code">
                                                        <i class="bx bx-paste"></i>
                                                    </button>
                                                </div>
                                                <div class="email-user-list list-group">
                                                    <form action="/admin/pages/template/file/{{ $template->name }}" method="post" class="js_ajax_form" novalidate>
                                                        {!! csrf_field() !!}
                                                        <div class="field-group mb-2 codemirror-wrapper">
                                                            <textarea rows="20" id="template_html" autocomplete="off" class="form-control form-control-sm" name="html">{{ !empty($template) ? $template->html : '' }}</textarea>
                                                        </div>
                                                        @if($me->hasAccess(['pages.write']))
                                                            <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-1">
                                                                <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                                    <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true"></span>
                                                                    {{ trans('locale.Save changes') }}
                                                                </button>
                                                                <button type="reset" class="btn btn-light">{{ trans('locale.Cancel') }}</button>
                                                            </div>
                                                        @endif
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade show" id="preview" aria-labelledby="preview-tab" role="tabpanel">
                            @if(!empty($preview_page))
                                <iframe src="{{ $preview_page->link() }}" style="width:100%;height:75vh;border:1px solid #3b4253;border-radius:4px;"></iframe>
                            @else
                                <div class="alert alert-warning">{{ trans('locale.This template is not linked to any page or block yet — create one to see a live preview.') }}</div>
                            @endif
                        </div>
                        <div class="tab-pane fade show" id="history" aria-labelledby="history-tab" role="tabpanel">
                            @if($revisions->isEmpty())
                                <div class="alert alert-warning">{{ trans('locale.No changes recorded yet') }}</div>
                            @else
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('locale.Date') }}</th>
                                            <th>{{ trans('locale.Type') }}</th>
                                            <th>{{ trans('locale.User') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($revisions as $revision)
                                            <tr>
                                                <td>{{ $revision->created_at->format('d.m.Y H:i') }}</td>
                                                <td>{{ str_ends_with($revision->entity, '_fields') ? trans('locale.Fields') : trans('locale.Template') }}</td>
                                                <td>{{ !empty($revision->user) ? $revision->user->name : '—' }}</td>
                                                <td class="text-right">
                                                    @if($me->hasAccess(['pages.write']))
                                                        <button type="button" class="btn btn-sm btn-outline-primary js_restore_revision" data-id="{{ $revision->id }}" data-name="{{ $template->name }}" data-app="pages">
                                                            {{ trans('locale.Restore') }}
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/extensions/dragula.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/select/select2.full.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/bootstrap-treeview.min.js')}}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    {{-- Addons: --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closetag.min.js"></script>
    {{-- Addons (fold): --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/foldcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/foldgutter.min.js"></script>
    {{--<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/brace-fold.min.js"></script>--}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/xml-fold.min.js"></script>
    {{--<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/indent-fold.min.js"></script>--}}
    {{--<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/markdown-fold.min.js"></script>--}}
    {{--<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/fold/comment-fold.min.js"></script>--}}
@endsection
{{-- page scripts --}}
@section('page-scripts')
    <script>
        window.treeviewData = {!! json_encode($treeview_data, JSON_UNESCAPED_UNICODE) !!}
    </script>

{{--    <script src="{{asset('js/admin/beautify.js')}}"></script>--}}
{{--    <script src="{{asset('js/admin/beautify-html.js')}}"></script>--}}
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/admin/pages-templates.js')}}"></script>
@endsection
