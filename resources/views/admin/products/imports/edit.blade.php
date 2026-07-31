@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Edit import'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/imports.css')}}">
@endsection

@section('content')
    <!-- import edit start -->
    <section class="import-edit">
        <form action="/admin/products/imports/update_file/{{ $import->id }}" method="post" class="hidden" enctype="multipart/form-data">
            @csrf
            <input type="file" name="import_file" id="js_file" accept="text/csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" onchange="this.form.submit()">
        </form>
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-2" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="settings-tab" data-toggle="tab"
                               href="#settings" aria-controls="settings" role="tab" aria-selected="false">
                                <i class="bx bx-slider-alt mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Settings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm active" id="fields-tab" data-toggle="tab"
                               href="#fields" aria-controls="fields" role="tab" aria-selected="false">
                                <i class="bx bx-menu mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.Relations') }}</span>
                            </a>
                        </li>
                        <li style="position: absolute;right: 10px;">
                            <label for="js_file" class="btn btn-sm btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1" style="cursor: pointer;">
                                <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                {{ trans('locale.Replace import file') }}
                            </label>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show" id="settings" aria-labelledby="settings-tab" role="tabpanel">
                            <!-- import edit Info form start -->
                            <form action="/admin/products/imports/{{ $import->id }}/update_settings" method="post" class="js_ajax_form" id="js_import_settings_form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'string',
                                                    'label' => trans('locale.Name'),
                                                    'field' => [
                                                      'key' => 'name',
                                                      'item' => $import,
                                                      'required' => true
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                   'type' => 'select',
                                                   'label' => trans('locale.Import type'),
                                                   'field' => [
                                                     'key' => 'type',
                                                     'selected' => [isset($import->settings->type) ?? $import->settings->type],
                                                     'options' => [
                                                        (object)['value' => 'create', 'name' => trans('locale.Add new products')],
                                                        (object)['value' => 'update', 'name' => trans('locale.Update existing products')],
                                                        (object)['value' => 'update_and_create', 'name' => trans('locale.Add new and update existing products')]
                                                     ],
                                                     'required' => true
                                                   ]
                                                ])
                                            </div>
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'select',
                                                    'label' => trans('locale.Relation field'),
                                                    'field' => [
                                                      'key' => 'relation',
                                                      'selected' => [isset($import->schedule->method) ?? $import->schedule->method],
                                                      'options' => [
                                                        (object)['value' => 'product.id', 'name' => trans('locale.Product ID')],
                                                        (object)['value' => 'localization.name_'.config()->get('app.locale'), 'name' => trans('locale.Product name')],
                                                        (object)['value' => 'product.sku', 'name' => trans('locale.SKU')]
                                                      ],
                                                      'required' => false
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['imports.write']))
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
                            <!-- import edit Info form ends -->
                        </div>
                        <div class="tab-pane active fade show" id="fields" aria-labelledby="fields-tab" role="tabpanel">
                            <!-- import edit Info form start -->
                            <form action="/admin/products/imports/{{ $import->id }}/update_fields" method="post" method="post" class="js_ajax_form fields-repeater" id="js_import_fields_form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>{{ trans('locale.Field') }}</th>
                                                        <th>{{ trans('locale.Example') }}</th>
                                                        <th>{{ trans('locale.Relation') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @php $i=0; @endphp
                                                @foreach($structure as $title => $data)
                                                    <tr class="field">
                                                        <td>
                                                            {{ $title }}
                                                        </td>
                                                        <td style="overflow: hidden">
                                                            {!! $data->data !!}
                                                        </td>
                                                        <td>
                                                            <input type="hidden" name="fields[{{ $i }}][title]" value="{{ $title }}">
                                                            <select name="fields[{{ $i }}][type]" class="form-control form-control-sm import-field-type">
                                                                <option value="">{{ trans('locale.Skip') }}</option>
                                                                @foreach($fields as $field => $name)
                                                                    <option value="{{ $field }}"{{ $data->type == $field ? ' selected' : '' }}>{{ $name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <div class="field-form" data-id="{{ $i }}">
                                                                @if($data->type == 'product.file_id' || $data->type == 'galleries.file_id')
                                                                    <label>{{ trans('locale.Content type') }}:</label>
                                                                    <select class="form-control form-control-sm" name="fields[{{ $i }}][format]">
                                                                        <option value="media.name"{{ isset($data->format) && $data->format == 'media.name' ? ' selected' : '' }}>{{ trans('locale.File name from media library') }}</option>
                                                                        @if(!empty($import->attachments))
                                                                            <option value="archive.name"{{ isset($data->format) && $data->format == 'archive.name' ? ' selected' : '' }}>{{ trans('locale.File name from archive') }}</option>
                                                                        @endif
                                                                        <option value="link"{{ isset($data->format) && $data->format == 'link' ? ' selected' : '' }}>{{ trans('locale.File URL') }}</option>
                                                                        <option value="media.id"{{ isset($data->format) && $data->format == 'media.id' ? ' selected' : '' }}>{{ trans('locale.File ID from media library') }}</option>
                                                                    </select>

                                                                    @if($data->type == 'galleries.file_id')
                                                                        <label>{{ trans('locale.Separator') }}:</label>
                                                                        <input class="form-control form-control-sm" type="text" name="fields[{{ $i }}][separator]" value="{{ isset($data->separator) ? $data->separator : '' }}">
                                                                    @endif

                                                                    <label>{{ trans('locale.If file not found') }}:</label>
                                                                    <select class="form-control form-control-sm" name="fields[{{ $i }}][not_found]">
                                                                        <option value="stop"{{ isset($data->not_found) && $data->not_found == 'stop' ? ' selected' : '' }}>{{ trans('locale.Stop import') }}</option>
                                                                        <option value="skip"{{ isset($data->not_found) && $data->not_found == 'skip' ? ' selected' : '' }}>{{ trans('locale.Skip product import') }}</option>
                                                                        <option value="ignore"{{ isset($data->not_found) && $data->not_found == 'ignore' ? ' selected' : '' }}>{{ trans('locale.Import without image') }}</option>
                                                                        <option value="remain"{{ isset($data->not_found) && $data->not_found == 'remain' ? ' selected' : '' }}>{{ trans('locale.Keep old image (on update)') }}</option>
                                                                    </select>
                                                                @elseif($data->type == 'category.id')
                                                                    <label>{{ trans('locale.Separator between categories') }}:</label>
                                                                    <input class="form-control form-control-sm" type="text" name="fields[{{ $i }}][separator]" value="{{ isset($data->separator) ? $data->separator : '' }}">

                                                                    <label>{{ trans('locale.Category nesting separator') }}:</label>
                                                                    <input class="form-control form-control-sm" type="text" name="fields[{{ $i }}][tree_separator]" value="{{ isset($data->tree_separator) ? $data->tree_separator : '' }}">

                                                                    <label>{{ trans('locale.If category not found') }}:</label>
                                                                    <select class="form-control form-control-sm" name="fields[{{ $i }}][not_found]">
                                                                        <option value="stop"{{ isset($data->not_found) && $data->not_found == 'stop' ? ' selected' : '' }}>{{ trans('locale.Stop import') }}</option>
                                                                        <option value="skip"{{ isset($data->not_found) && $data->not_found == 'skip' ? ' selected' : '' }}>{{ trans('locale.Skip product import') }}</option>
                                                                        <option value="ignore"{{ isset($data->not_found) && $data->not_found == 'ignore' ? ' selected' : '' }}>{{ trans('locale.Import without category') }}</option>
                                                                        <option value="remain"{{ isset($data->not_found) && $data->not_found == 'remain' ? ' selected' : '' }}>{{ trans('locale.Keep old categories (on update)') }}</option>
                                                                        <option value="create"{{ isset($data->not_found) && $data->not_found == 'create' ? ' selected' : '' }}>{{ trans('locale.Create new category') }}</option>
                                                                    </select>
                                                                @elseif($data->type == 'attribute_values.id')
                                                                    <label>{{ trans('locale.Content type') }}:</label>
                                                                    <select class="form-control form-control-sm attributes-format" name="fields[{{ $i }}][format]">
                                                                        <option value="values"{{ isset($data->format) && $data->format == 'values' ? ' selected' : '' }}>{{ trans('locale.Values of a single attribute') }}</option>
                                                                        <option value="attributes_and_values"{{ isset($data->format) && $data->format == 'attributes_and_values' ? ' selected' : '' }}>{{ trans('locale.Attribute names and their values') }}</option>
                                                                    </select>

                                                                    <div class="attribute-fields">
                                                                        @if(isset($data->format) && $data->format == 'attributes_and_values')
                                                                            <label>{{ trans('locale.Separator between attributes') }}:</label>
                                                                            <input class="form-control form-control-sm" type="text" name="fields[{{ $i }}][attributes_separator]" value="{{ isset($data->attributes_separator) ? $data->attributes_separator : '' }}">
                                                                            <label>{{ trans('locale.Separator between attribute name and its values') }}:</label>
                                                                            <input class="form-control form-control-sm" type="text" name="fields[{{ $i }}][attribute_values_separator]" value="{{ isset($data->attribute_values_separator) ? $data->attribute_values_separator : '' }}">
                                                                        @else
                                                                            <label>{{ trans('locale.Attribute name') }}:</label>
                                                                            <input class="form-control form-control-sm" type="text" name="fields[{{ $i }}][attribute]" value="{{ isset($data->attribute) ? $data->attribute : '' }}">
                                                                        @endif
                                                                    </div>

                                                                    <label>{{ trans('locale.Separator between attribute values') }}:</label>
                                                                    <input class="form-control form-control-sm" type="text" name="fields[{{ $i }}][separator]" value="{{ isset($data->separator) ? $data->separator : '' }}">

                                                                    <label>{{ trans('locale.If attribute not found') }}:</label>
                                                                    <select class="form-control form-control-sm" name="fields[{{ $i }}][not_found]">
                                                                        <option value="stop"{{ isset($data->not_found) && $data->not_found == 'stop' ? ' selected' : '' }}>{{ trans('locale.Stop import') }}</option>
                                                                        <option value="skip"{{ isset($data->not_found) && $data->not_found == 'skip' ? ' selected' : '' }}>{{ trans('locale.Skip product import') }}</option>
                                                                        <option value="ignore"{{ isset($data->not_found) && $data->not_found == 'ignore' ? ' selected' : '' }}>{{ trans('locale.Import without attribute') }}</option>
                                                                        <option value="remain"{{ isset($data->not_found) && $data->not_found == 'remain' ? ' selected' : '' }}>{{ trans('locale.Keep old attributes (on update)') }}</option>
                                                                        <option value="create"{{ isset($data->not_found) && $data->not_found == 'create' ? ' selected' : '' }}>{{ trans('locale.Create new attribute') }}</option>
                                                                    </select>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php $i++; @endphp
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['imports.write']))
                                        <div class="col-12 mt-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    @if($import->status == 100)
                                                        <button type="button" class="btn btn-primary" id="js_refresh_import" data-id="{{ $import->id }}">{{ trans('locale.Repeat import') }}</button>
                                                    @else
                                                        <button class="btn btn-primary" id="js_start_import" data-id="{{ $import->id }}" type="button">
                                                            {{ $import->status == 0 ? trans('locale.Start import') : trans('locale.Continue import') }}
                                                        </button>
                                                    @endif
                                                </div>
                                                <div class="col-6 d-flex flex-sm-row flex-column justify-content-end">
                                                    <button type="submit" class="btn btn-primary glow mb-1 mb-sm-0 mr-0 mr-sm-1">
                                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true" style="top: -2px; position: relative;"></span>
                                                        {{ trans('locale.Save changes') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-2 hidden" id="import_process">
                                            <div class="progress progress-label progress-bar-primary mb-2">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
                                            </div>

                                            <div class="alert alert-success mb-2 hidden"></div>

                                            <div class="alert alert-warning mb-2 hidden"></div>

                                            <div class="alert alert-danger mb-2 hidden"></div>
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- import edit Info form ends -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- category edit ends -->
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
    <script src="{{asset('vendors/js/forms/validation/jqBootstrapValidation.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/sweetalert2.all.min.js')}}"></script>
    <script src="{{asset('vendors/js/extensions/toastr.min.js')}}"></script>
    <script src="{{asset('vendors/js/forms/repeater/jquery.repeater.js')}}"></script>
    <script src="{{asset('vendors/js/forms/spinner/jquery.bootstrap-touchspin.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/admin/imports.js')}}"></script>
    @include('admin.media.assets')
@endsection
