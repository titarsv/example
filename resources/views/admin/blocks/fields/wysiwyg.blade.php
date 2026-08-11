<div class="field-group"@if(!empty($field->conditional_field)) data-conditional-field="{{ $field->conditional_field }}" data-conditional-operator="{{ !empty($field->conditional_operator) ? $field->conditional_operator : '==' }}" data-conditional-value="{{ $field->conditional_value ?? '' }}"@endif>
    <label>{{ $field->name }}</label>
            @if(!empty($field->instructions))<small class="text-muted d-block mb-1">{{ $field->instructions }}</small>@endif
    <div class="row">
        @if(!empty($field->langs))
            @foreach($fields as $lang => $lang_fields)
                <div class="col lang-field js-lang-{{ $lang }}{{ $main_lang == $lang ? ' active_lang' : '' }}">
                    <div class="form-group">
                        @include('admin.layouts.texteditor', [
                            'content' => isset($fields[$lang][$key]->value) ? $fields[$lang][$key]->value : '',
                            'editor_id' => 'fields['.$lang.']'.(!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '').'['.$field->slug.']',
                            'placeholder' => $locales_names[$lang],
                            'data-prefix' => 'fields['.$lang.']',
                            'data-name' => $field->slug
                           ])
                    </div>
                </div>
            @endforeach
        @else
            <div class="col">
                <div class="form-group">
                    @include('admin.layouts.texteditor', [
                       'content' => isset($field->value) ? $field->value : '',
                       'editor_id' => 'fields['.(isset($main_key) ? $main_key : 'all').']'.(!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '').'['.$field->slug.']',
                       'placeholder' => $locales_names[$main_lang],
                       'data-prefix' => 'fields['.(isset($main_key) ? $main_key : 'all').']',
                       'data-name' => $field->slug
                   ])
                </div>
            </div>
        @endif
    </div>
</div>
