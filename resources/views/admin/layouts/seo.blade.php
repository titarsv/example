<div class="field-group">
    <div class="row">
        <div class="col-sm-6">
            <div class="form-group">
                <label>@lang('locale.URL')</label>
                <fieldset>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">{{ ENV('APP_URL') }}</span>
                        </div>
                        <input type="text"
                               class="form-control form-control-sm{{ $errors->has('url') ? ' is-invalid' : '' }}"
                               name="url"
                               value="{{ old('url') ? old('url') : (isset($seo) && isset($seo->url) ? $seo->url : '') }}"
                               {{ !empty($required_url) ? ' required' : '' }} />
                    </div>
                </fieldset>
                <div class="help-block"></div>
                @if($errors->has('url'))
                    <div class="invalid-tooltip">
                        {{ $errors->first('url',':message') }}
                    </div>
                @endif
            </div>
        </div>
        <div class="col-sm-6">
            <label>@lang('locale.Robots')<i class="bx bx-info-circle"
                            data-toggle="popover"
                            data-placement="bottom"
                            data-trigger="hover"
                            data-content="@lang('locale.robots_hint')"
                ></i></label>
            @include('admin.layouts.form.select', [
             'key' => 'robots',
             'options' => collect(config('app.locales'))->mapWithKeys(function($locale) {
                app()->setLocale($locale);
                return [
                    'all' => (object)['value' => 'index, follow', 'name' => trans('locale.robots_options.all')],
                    'noindex' => (object)['value' => 'noindex', 'name' => trans('locale.robots_options.noindex')],
                    'nofollow' => (object)['value' => 'nofollow', 'name' => trans('locale.robots_options.nofollow')],
                    'none' => (object)['value' => 'noindex, nofollow', 'name' => trans('locale.robots_options.none')],
                    'noarchive' => (object)['value' => 'noarchive', 'name' => trans('locale.robots_options.noarchive')],
                    'nosnippet' => (object)['value' => 'nosnippet', 'name' => trans('locale.robots_options.nosnippet')],
                    'notranslate' => (object)['value' => 'notranslate', 'name' => trans('locale.robots_options.notranslate')],
                    'noimageindex' => (object)['value' => 'noimageindex', 'name' => trans('locale.robots_options.noimageindex')],
                ];
             })->values()->toArray(),
             'selected' => [old('robots', isset($seo) ? $seo->robots : '')],
             'languages' => null
            ])
        </div>
    </div>
</div>
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => trans('locale.H1'),
    'field' => [
        'key' => 'seo_name',
        'item' => isset($seo) ? $seo : null
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => trans('locale.Meta Title'),
    'field' => [
        'key' => 'meta_title',
        'item' => isset($seo) ? $seo : null,
        'max_length' => 60
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'text',
    'label' => trans('locale.Meta Description'),
    'field' => [
        'key' => 'meta_description',
        'item' => isset($seo) ? $seo : null,
        'max_length' => 180
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'editor',
    'label' => trans('locale.Description'),
    'field' => [
        'key' => 'seo_description',
        'item' => isset($seo) ? $seo : null
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => trans('locale.Meta Keywords'),
    'field' => [
        'key' => 'meta_keywords',
        'item' => isset($seo) ? $seo : null
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => trans('locale.Canonical URL'),
    'languages' => null,
    'field' => [
        'key' => 'canonical',
        'item' => isset($seo) ? $seo : null,
    ]
])
@if(!isset($seo))
@include('admin.layouts.form.field-group', [
    'type' => 'select',
    'label' => trans('locale.Page Type'),
    'field' => [
        'key' => 'seotable_type',
        'options' => isset($types) ? $types : (isset($seo) ? $seo->types_select_data : []),
        'selected' => [old('seotable_type', isset($seo) ? $seo->seotable_type : '')],
        'languages' => null
    ]
])
<div class="form-group" id="js_seotable_data" style="display: none">
    <div class="row">
        <div class="col-sm-6">
            @include('admin.layouts.form.field-group', [
                'type' => 'string',
                'label' => trans('locale.Record ID'),
                'languages' => null,
                'field' => [
                    'key' => 'seotable_id',
                    'item' => null,
                ]
            ])
        </div>
        <div class="col-sm-6">
            @include('admin.layouts.form.field-group', [
                'type' => 'string',
                'label' => trans('locale.Display Method'),
                'languages' => null,
                'field' => [
                    'key' => 'action',
                    'item' => null,
                ]
            ])
        </div>
    </div>
</div>
@endif
