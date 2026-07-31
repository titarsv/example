<div class="field-group">
    <div class="row">
        <div class="col-sm-6">
            <div class="form-group">
                <label>Url</label>
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
            <label>Robots<i class="bx bx-info-circle"
                            data-toggle="popover"
                            data-placement="bottom"
                            data-trigger="hover"
                            data-content="Доступны следующие значения: all, noindex, nofollow, none, noarchive, nosnippet, notranslate, noimageindex"
                ></i></label>
            @include('admin.layouts.form.select', [
             'key' => 'robots',
             'options' => [
                (object)['value' => 'all', 'name' => 'Нет ограничений на индексирование и показ контента.'],
                (object)['value' => 'noindex', 'name' => 'Не показывать эту страницу в результатах поиска.'],
                (object)['value' => 'nofollow', 'name' => 'Не выполнять переход по ссылкам на странице.'],
                (object)['value' => 'none', 'name' => 'Не показывать эту страницу в результатах поиска и не выполнять переход по ссылкам на странице.'],
                (object)['value' => 'noarchive', 'name' => 'Не показывать ссылку на кеш в результатах поиска.'],
                (object)['value' => 'nosnippet', 'name' => 'Не показывать в результатах поиска текстовый фрагмент или видео.'],
                (object)['value' => 'notranslate', 'name' => 'Не предлагать перевести эту страницу в результатах поиска.'],
                (object)['value' => 'noimageindex', 'name' => 'Не индексировать изображения на странице.'],
             ],
             'selected' => [old('robots', isset($seo) ? $seo->robots : '')],
             'languages' => null
            ])
        </div>
    </div>
</div>
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => 'H1',
    'field' => [
        'key' => 'seo_name',
        'item' => isset($seo) ? $seo : null,
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => 'Title',
    'field' => [
        'key' => 'meta_title',
        'item' => isset($seo) ? $seo : null,
        'max_length' => 60
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'text',
    'label' => 'Meta description',
    'field' => [
        'key' => 'meta_description',
        'item' => isset($seo) ? $seo : null,
        'max_length' => 180
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'editor',
    'label' => 'Описание',
    'field' => [
        'key' => 'seo_description',
        'item' => isset($seo) ? $seo : null
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => 'Meta keywords',
    'field' => [
        'key' => 'meta_keywords',
        'item' => isset($seo) ? $seo : null
    ]
])
@include('admin.layouts.form.field-group', [
    'type' => 'string',
    'label' => 'Canonical',
    'languages' => null,
    'field' => [
        'key' => 'canonical',
        'item' => isset($seo) ? $seo : null,
    ]
])
@if(!isset($seo))
@include('admin.layouts.form.field-group', [
    'type' => 'select',
    'label' => 'Тип страницы',
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
                'label' => 'ID записи',
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
                'label' => 'Метод отображения',
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