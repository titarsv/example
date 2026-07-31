@php
    $key = $field['key'];
@endphp
<div class="field-group">
    <label>{{ $label }}</label>
    @if(in_array($type, ['string', 'text', 'editor']) && !empty($languages) && count($languages) > 1 && (!isset($field['item']) || (!is_array($field['item']->localized_fields) || in_array($key, $field['item']->localized_fields))))
        <div class="row">
        @foreach($languages as $lang_key => $lang_name)
            @php
                $field['key'] = $key.'_'.$lang_key;
                $field['placeholder'] = $lang_name;
                $field['value'] = old($key.'_'.$lang_key) ? old($key.'_'.$lang_key) : (isset($field['item']) ? $field['item']->localize($lang_key, $key) : '');
            @endphp
            <div class="col lang-field js-lang-{{ $lang_key }}">
                @include('admin.layouts.form.'.$type, $field)
            </div>
        @endforeach
        </div>
    @else
        @php
            $field['value'] = old($key) ? old($key) : (isset($locale) ? (isset($field['item']) ? $field['item']->localize($locale, $key) : '') : (isset($field['item']->$key) ? $field['item']->$key : ''));
        @endphp
        @include('admin.layouts.form.'.$type, $field)
    @endif
</div>
