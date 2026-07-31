@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.export.edit_export'))
{{-- vendor styles --}}
@section('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/plugins/forms/validation/form-validation.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/forms/select/select2.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/pickers/pickadate/pickadate.css')}}">
    <link rel="stylesheet" type="text/css" href="{{asset('vendors/css/extensions/toastr.css')}}">
@endsection

{{-- page styles --}}
@section('page-styles')
    <link rel="stylesheet" type="text/css" href="{{asset('css/admin/exports.css')}}">
@endsection

@section('content')
    <!-- export edit start -->
    <section class="export-edit">
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
                                <i class="bx bx-menu mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.export.export_fields') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center btn-sm" id="filter-tab" data-toggle="tab"
                                  href="#filter" aria-controls="filter" role="tab" aria-selected="false">
                                <i class="bx bx-filter mr-25"></i><span class="d-none d-sm-block">{{ trans('locale.export.products_filter') }}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show" id="settings" aria-labelledby="settings-tab" role="tabpanel">
                            <!-- export edit Info form start -->
                            <form action="/admin/products/exports/{{ $export->id }}/update_settings" method="post" class="js_ajax_form" id="js_export_settings_form" novalidate>
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
                                                      'item' => $export,
                                                      'required' => true
                                                    ]
                                                ])
                                                <div class="form-group">
                                                    <label>{{ trans('locale.URL') }}</label>
                                                    <fieldset>
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">{{ ENV('APP_URL') }}/exports/</span>
                                                            </div>
                                                            <input type="text"
                                                                   class="form-control form-control-sm"
                                                                   name="url"
                                                                   value="{{ $export->url }}"/>
                                                            <div class="input-group-append">
                                                                <span class="input-group-text" id="js_extension">.csv</span>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>
                                            </div>
                                            <div class="col">
                                                @include('admin.layouts.form.field-group', [
                                                   'type' => 'select',
                                                   'label' => trans('locale.Type'),
                                                   'field' => [
                                                     'key' => 'type',
                                                     'selected' => [$export->type],
                                                     'options' => [(object)['value' => 'csv', 'name' => 'csv'], (object)['value' => 'xls', 'name' => 'xls'], (object)['value' => 'xml', 'name' => 'xml'], (object)['value' => 'rss', 'name' => 'rss'], (object)['value' => 'json', 'name' => 'json']],
                                                     'required' => true
                                                   ]
                                                ])
                                                @include('admin.layouts.form.field-group', [
                                                    'type' => 'select',
                                                    'label' => trans('locale.export.update_frequency'),
                                                    'field' => [
                                                      'key' => 'schedule',
                                                      'selected' => [isset($export->schedule->method) ? $export->schedule->method : ''],
                                                      'options' => [
                                                        (object)['value' => '', 'name' => trans('locale.export.do_not_update')],
                                                        (object)['value' => 'everyMinute', 'name' => trans('locale.export.every_minute')],
                                                        (object)['value' => 'everyFiveMinutes', 'name' => trans('locale.export.every_five_minutes')],
                                                        (object)['value' => 'everyTenMinutes', 'name' => trans('locale.export.every_ten_minutes')],
                                                        (object)['value' => 'everyThirtyMinutes', 'name' => trans('locale.export.every_thirty_minutes')],
                                                        (object)['value' => 'hourly', 'name' => trans('locale.export.hourly')],
                                                        (object)['value' => 'daily', 'name' => trans('locale.export.daily')],
                                                        (object)['value' => 'weekly', 'name' => trans('locale.export.weekly')],
                                                        (object)['value' => 'monthly', 'name' => trans('locale.export.monthly')],
                                                        (object)['value' => 'quarterly', 'name' => trans('locale.export.quarterly')],
                                                        (object)['value' => 'yearly', 'name' => trans('locale.export.yearly')]
                                                      ],
                                                      'required' => false
                                                    ]
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['exports.write']))
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
                            <!-- export edit Info form ends -->
                        </div>
                        <div class="tab-pane active fade show" id="fields" aria-labelledby="fields-tab" role="tabpanel">
                            <!-- export edit Info form start -->
                            <form action="/admin/products/exports/{{ $export->id }}/update_fields" method="post" method="post" class="js_ajax_form fields-repeater" id="js_export_fields_form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <div class="row">
                                            <div class="col">
                                                <div class="row justify-content-between">
                                                    <div class="col" style="flex-grow: 1;">
                                                        <label>{{ trans('locale.Name') }}</label>
                                                    </div>
                                                    <div class="col" style="flex-grow: 1;">
                                                        <label>{{ trans('locale.export.field') }}</label>
                                                    </div>
                                                    <div class="col" style="flex-grow: 1;">
                                                        <label>{{ trans('locale.export.modifiers') }}</label>
                                                    </div>
                                                    <div class="col" style="flex-grow: 0; min-width: 127px;">

                                                    </div>
                                                </div>
                                                <div id="js_attribute_values_wrapper">
                                                    <div data-repeater-list="fields">
                                                        @if(empty($export->structure))
                                                            <div class="row js_export_field" data-id="0" data-repeater-item="">
                                                                <div class="form-element col" style="flex-grow: 1;">
                                                                    <input type="text" class="form-control form-control-sm" name="fields[0][name]" placeholder="{{ trans('locale.Name') }}" value="{{ old('fields[0][name]') }}" autocomplete="off" />
                                                                </div>
                                                                <div class="form-element col" style="flex-grow: 1;">
                                                                    <select class="form-control form-control-sm field_type" name="fields[0][field][type]" autocomplete="off">
                                                                        @foreach($field_types as $field_type => $name)
                                                                            <option value="{{ $field_type }}">{{ $name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="form-element col modifications fields-modifications-repeater" style="flex-grow: 1;">
                                                                    <div class="modifications" data-repeater-list="modifications">
                                                                        <fieldset class="modification" data-id="0" data-repeater-item="">
                                                                            <div class="input-group input-group-sm mb-1">
                                                                                <select class="form-control form-control-sm" name="fields[0][modifications][0][type]" autocomplete="off">
                                                                                    @foreach($modifications as $key => $modification)
                                                                                        <option value="{{ $key }}">{{ $modification }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <div class="input-group-append">
                                                                                    <button type="button" class="btn btn-danger btn-sm" data-repeater-delete="">
                                                                                        <i class="bx bx-minus-circle"></i>
                                                                                    </button>
                                                                                    <button type="button" class="btn btn-primary btn-sm" data-repeater-create="">
                                                                                        <i class="bx bxs-plus-square"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        </fieldset>
                                                                    </div>
                                                                </div>
                                                                <div class="form-element col text-right" style="flex-grow: 0; min-width:127px;">
                                                                    <button class="btn btn-danger btn-sm text-nowrap px-1" data-repeater-delete="" type="button"> <i class="bx bx-x"></i>
                                                                        {{ trans('locale.Delete') }}
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @else
                                                            @foreach($export->structure as $fi => $field)
                                                                <div class="row js_export_field" data-id="{{ $fi }}" data-repeater-item="">
                                                                    <div class="form-element col" style="flex-grow: 1;">
                                                                        <input type="text" class="form-control form-control-sm" name="fields[{{ $fi }}][name]" placeholder="{{ trans('locale.Name') }}" value="{{ $field->name }}" autocomplete="off" />
                                                                    </div>
                                                                    <div class="form-element col" style="flex-grow: 1;">
                                                                        <select class="form-control form-control-sm field_type" name="fields[{{ $fi }}][field][type]" autocomplete="off">
                                                                            @foreach($export->getFieldTypes() as $field_type => $name)
                                                                                <option value="{{ $field_type }}"{{ $field->type == $field_type ? ' selected' : '' }}>{{ $name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        @if(!empty($field->field->custom))
                                                                            <input type="text" class="form-control form-control-sm" name="fields[{{ $fi }}][field][custom]" placeholder="{{ trans('locale.export.enter_custom_value') }}" value="{{ $field->field->custom }}">
                                                                        @elseif(!empty($field->field->attribute))
                                                                            <select class="form-control form-control-sm" name="fields[{{ $fi }}][field][attribute]">
                                                                                @foreach($all_attributes as $attr)
                                                                                    <option value="{{ $attr['id'] }}"{{ $attr['id'] == $field->field->attribute ? ' selected' : '' }}>{{ $attr['name'] }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif
                                                                    </div>
                                                                    <div class="form-element col modifications fields-modifications-repeater" style="flex-grow: 1;">
                                                                        <div class="modifications" data-repeater-list="modifications">
                                                                            @foreach($field->modifications as $i => $field_modification)
                                                                                @if(isset($field_modification->type))
                                                                                    <fieldset class="modification" data-id="{{ $i }}" data-repeater-item="">
                                                                                        <div class="input-group input-group-sm mb-1">
                                                                                            <select class="form-control form-control-sm" name="fields[{{ $fi }}][modifications][{{ $i }}][type]" autocomplete="off">
                                                                                                @foreach($export->getModifications() as $key => $modification)
                                                                                                    <option value="{{ $key }}"{{ $field_modification->type == $key ? ' selected' : '' }}>{{ $modification }}</option>
                                                                                                @endforeach
                                                                                            </select>
                                                                                            <div class="input-group-append">
                                                                                                <button type="button" class="btn btn-danger btn-sm" data-repeater-delete="">
                                                                                                    <i class="bx bx-minus-circle"></i>
                                                                                                </button>
                                                                                                <button type="button" class="btn btn-primary btn-sm" data-repeater-create="">
                                                                                                    <i class="bx bxs-plus-square"></i>
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                        @if(isset($field_modification->value))
                                                                                            <input type="text" class="form-control form-control-sm mb-1" name="fields[{{ $fi }}][modifications][{{ $i }}][value]" placeholder="{{ trans('locale.export.enter_value') }}" value="{{ $field_modification->value }}">
                                                                                        @elseif(isset($field_modification->from) && isset($field_modification->to))
                                                                                            <input type="text" class="form-control form-control-sm mb-1" name="fields[{{ $fi }}][modifications][{{ $i }}][from]" placeholder="{{ trans('locale.export.what_to_replace') }}" value="{{ $field_modification->from }}">
                                                                                            <input type="text" class="form-control form-control-sm mb-1" name="fields[{{ $fi }}][modifications][{{ $i }}][to]" placeholder="{{ trans('locale.export.replace_with') }}" value="{{ $field_modification->to }}">
                                                                                        @endif
                                                                                    </fieldset>
                                                                                @else
                                                                                    <fieldset class="modification" data-id="{{ $i }}" data-repeater-item="">
                                                                                        <div class="input-group input-group-sm mb-1">
                                                                                            <select class="form-control form-control-sm" name="fields[{{ $fi }}][modifications][0][type]" autocomplete="off">
                                                                                                @foreach($modifications as $key => $modification)
                                                                                                    <option value="{{ $key }}">{{ $modification }}</option>
                                                                                                @endforeach
                                                                                            </select>
                                                                                            <div class="input-group-append">
                                                                                                <button type="button" class="btn btn-danger btn-sm" data-repeater-delete="">
                                                                                                    <i class="bx bx-minus-circle"></i>
                                                                                                </button>
                                                                                                <button type="button" class="btn btn-primary btn-sm" data-repeater-create="">
                                                                                                    <i class="bx bxs-plus-square"></i>
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </fieldset>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-element col text-right" style="flex-grow: 0; min-width:127px;">
                                                                        <button class="btn btn-danger btn-sm text-nowrap px-1" data-repeater-delete="" type="button"> <i class="bx bx-x"></i>
                                                                            {{ trans('locale.Delete') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($me->hasAccess(['exports.write']))
                                        <div class="col-12 mt-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    <button class="btn btn-primary" data-repeater-create="" type="button"><i class="bx bx-plus"></i>
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
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- export edit Info form ends -->
                        </div>
                        <div class="tab-pane fade show" id="filter" aria-labelledby="filter-tab" role="tabpanel">
                            <!-- export edit Info form start -->
                            <form action="/admin/products/exports/{{ $export->id }}/update_filter" method="post" class="js_ajax_form filter_form" id="js_export_filter_form" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                @foreach($export->filters as $ig => $filter_group)
                                                    <div class="card form-group text-white bg-danger bg-lighten-1 text-center js_filters_group" data-group-id="{{ $ig }}">
                                                        <div class="card-content">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    @if($ig > 0)
                                                                        <fieldset class="col form-group mb-1 mt-1" style="min-width: 320px;">
                                                                            <div class="input-group input-group-sm">
                                                                                <div class="input-group-prepend">
                                                                                    <label class="input-group-text" for="inputGroupSelect{{ $ig }}">{{ trans('locale.export.group_relation_method') }}:</label>
                                                                                </div>
                                                                                <select name="filter[1][0][relations]" id="inputGroupSelect{{ $ig }}" class="form-control form-control-sm relations" style="min-width: 70px;" autocomplete="off">
                                                                                    <option value="AND"{{ $filter_group[0]->relations == 'AND' ? ' selected' : '' }}>{{ trans('locale.export.and') }}</option>
                                                                                    <option value="OR"{{ $filter_group[0]->relations == 'OR' ? ' selected' : '' }}>{{ trans('locale.export.or') }}</option>
                                                                                </select>
                                                                            </div>
                                                                        </fieldset>
                                                                    @endif
                                                                    <div class="col"></div>
                                                                    <div class="col mb-1 mt-1 d-flex flex-sm-row flex-column justify-content-end">
                                                                        <button type="button" class="btn btn-danger btn-sm text-nowrap px-1 js_remove_group">
                                                                            <i class="bx bx-x"></i>{{ trans('locale.export.delete_group') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                @foreach($filter_group as $if => $filter)
                                                                    <div class="row condition-wrapper">
                                                                        <div class="col">
                                                                            @if($if > 0)
                                                                                <div class="row">
                                                                                    <fieldset class="col form-group mb-1 mt-1" style="min-width: 320px;">
                                                                                        <div class="input-group input-group-sm">
                                                                                            <div class="input-group-prepend">
                                                                                                <label class="input-group-text" for="inputGroupSelect01">{{ trans('locale.export.condition_relation_method') }}:</label>
                                                                                            </div>
                                                                                            <select name="filter[{{ $ig }}][{{ $if }}][relations]" class="form-control form-control-sm relations" style="min-width: 70px;">
                                                                                                <option value="AND"{{ $filter->relations == 'AND' ? ' selected' : '' }}>{{ trans('locale.export.and') }}</option>
                                                                                                <option value="OR"{{ $filter->relations == 'OR' ? ' selected' : '' }}>{{ trans('locale.export.or') }}</option>
                                                                                            </select>
                                                                                        </div>
                                                                                    </fieldset>
                                                                                    <div class="col"></div>
                                                                                    <div class="col mb-1 mt-1 d-flex flex-sm-row flex-column justify-content-end">
                                                                                        <button type="button" class="btn btn-danger btn-sm text-nowrap px-1 js_remove_condition">
                                                                                            <i class="bx bx-x"></i>{{ trans('locale.export.delete_condition') }}
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                            <div class="row condition" data-id="{{ $if }}">
                                                                                <div class="form-element col">
                                                                                    <label class="text-right">{{ trans('locale.export.filter_criterion') }}:</label>
                                                                                    <select name="filter[{{ $ig }}][{{ $if }}][criterion]" class="form-control form-control-sm criterion" autocomplete="off">
                                                                                        @foreach([
                                                                                        '' => '',
                                                                                        'category' => trans('locale.Category'),
                                                                                        'attribute' => trans('locale.Attribute'),
                                                                                        'stock' => trans('locale.Stock'),
                                                                                        'price' => trans('locale.Price'),
                                                                                        'description' => trans('locale.Description')
                                                                                    ] as $key => $val)
                                                                                            <option value="{{ $key }}"{{ $key == $filter->criterion ? ' selected' : '' }}>{{ $val }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </div>
                                                                                @if($filter->criterion == 'category')
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Value') }}:</label>
                                                                                        <select name="filter[{{ $ig }}][{{ $if }}][value]" class="form-control form-control-sm value" autocomplete="off">
                                                                                            @foreach($categories as $category)
                                                                                                <option value="{{ $category->id }}"{{ $filter->value == $category->id ? ' selected' : '' }}>{{ $category->name }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    </div>
                                                                                @elseif($filter->criterion == 'attribute')
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.Attribute') }}:</label>
                                                                                        <select name="filter[{{ $ig }}][{{ $if }}][attribute]" class="form-control form-control-sm value" autocomplete="off">
                                                                                            @foreach($all_attributes as $attribute)
                                                                                                <option value="{{ $attribute['id'] }}"{{ $filter->attribute == $attribute['id'] ? ' selected' : '' }}>{{ $attribute['name'] }}</option>
                                                                                                @php
                                                                                                    if($filter->attribute == $attribute['id']){
                                                                                                        $current_attribute = $attribute;
                                                                                                    }
                                                                                                @endphp
                                                                                            @endforeach
                                                                                            @php
                                                                                                if(!isset($current_attribute)){
                                                                                                    $current_attribute = $all_attributes[0];
                                                                                                }
                                                                                            @endphp
                                                                                        </select>
                                                                                    </div>
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Value') }}:</label>
                                                                                        <select name="filter[{{ $ig }}][{{ $if }}][value]" class="form-control form-control-sm value" autocomplete="off">
                                                                                            @foreach($current_attribute['values'] as $value)
                                                                                                <option value="{{ $value['id'] }}"{{ $filter->value == $value['id'] ? ' selected' : ''  }}>{{ $value['name'] }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    </div>
                                                                                @elseif($filter->criterion == 'stock')
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Value') }}:</label>
                                                                                        <select name="filter[{{ $ig }}][{{ $if }}][value]" class="form-control form-control-sm value" autocomplete="off">
                                                                                            <option value="0"{{ $filter->value == 0 ? ' selected' : '' }}>{{ trans('locale.Out of stock') }}</option>
                                                                                            <option value="1"{{ $filter->value == 1 ? ' selected' : '' }}>{{ trans('locale.In stock') }}</option>
                                                                                        </select>
                                                                                    </div>
                                                                                @elseif($filter->criterion == 'price')
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Condition') }}:</label>
                                                                                        <select name="filter[{{ $ig }}][{{ $if }}][condition]" class="form-control form-control-sm value" autocomplete="off">
                                                                                            <option value="="{{ $filter->condition == '=' ? ' selected' : '' }}>{{ trans('locale.export.equals') }}</option>
                                                                                            <option value=">"{{ $filter->condition == '>' ? ' selected' : '' }}>{{ trans('locale.export.greater_than') }}</option>
                                                                                            <option value="<"{{ $filter->condition == '<' ? ' selected' : '' }}>{{ trans('locale.export.less_than') }}</option>
                                                                                        </select>
                                                                                    </div>
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Value') }}:</label>
                                                                                        <div class="input-group input-group-sm bootstrap-touchspin bootstrap-touchspin-injected">
                                                                                            <input type="text" name="filter[{{ $ig }}][{{ $if }}][value]" value="{{ $filter->value }}" class="touchspin form-control form-control-sm value" data-bts-prefix="₴">
                                                                                        </div>
                                                                                    </div>
                                                                                @elseif($filter->criterion == 'description')
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Condition') }}:</label>
                                                                                        <select name="filter[{{ $ig }}][{{ $if }}][condition]" class="form-control form-control-sm price_condition">
                                                                                            <option value="="{{ $filter->condition == '=' ? ' selected' : '' }}>{{ trans('locale.export.equals') }}</option>
                                                                                            <option value="%"{{ $filter->condition == '%' ? ' selected' : '' }}>{{ trans('locale.export.contains') }}</option>
                                                                                        </select>
                                                                                    </div>
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Value') }}:</label>
                                                                                        <input type="text" name="filter[{{ $ig }}][{{ $if }}][value]" value="{{ $filter->value }}" class="form-control form-control-sm value">
                                                                                    </div>
                                                                                @else
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Value') }}:</label>
                                                                                    </div>
                                                                                    <div class="form-element col">
                                                                                        <label class="text-right">{{ trans('locale.export.Condition') }}:</label>
                                                                                    </div>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                                <div class="row mt-1">
                                                                    <div class="col text-center buttons">
                                                                        <button type="button" class="btn btn-sm btn-primary js_add_sub_condition">{{ trans('locale.Add condition') }}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    @if($me->hasAccess(['exports.write']))
                                        <div class="col-12 mt-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    <button type="button" class="btn btn-primary" id="js_add_condition_group"><i class="bx bx-plus"></i>
                                                        {{ trans('locale.Add a condition group') }}
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
                                        </div>
                                    @endif
                                </div>
                            </form>
                            <!-- export edit Info form ends -->
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
    <script>
        window.categories = {!! json_encode($categories, JSON_UNESCAPED_UNICODE) !!};
        window.actions = {!! json_encode($actions, JSON_UNESCAPED_UNICODE) !!};
        window.attributes = {!! json_encode($all_attributes, JSON_UNESCAPED_UNICODE) !!};
        window.export = {
            field_types: {!! json_encode($export->getFieldTypes(), JSON_UNESCAPED_UNICODE) !!},
            modifications: {!! json_encode($export->getModifications(), JSON_UNESCAPED_UNICODE) !!}
        };
    </script>
    <script src="{{asset('js/admin/admin.js')}}"></script>
    <script src="{{asset('js/scripts/navs/navs.js')}}"></script>
    <script src="{{asset('js/scripts/forms/validation/form-validation.js')}}"></script>
    <script src="{{asset('js/admin/exports.js')}}"></script>
    @include('admin.media.assets')
@endsection
