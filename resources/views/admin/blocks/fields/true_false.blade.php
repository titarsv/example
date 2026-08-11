<div class="field-group"@if(!empty($field->conditional_field)) data-conditional-field="{{ $field->conditional_field }}" data-conditional-operator="{{ !empty($field->conditional_operator) ? $field->conditional_operator : '==' }}" data-conditional-value="{{ $field->conditional_value ?? '' }}"@endif>
    <label>{{ $field->name }}</label>
            @if(!empty($field->instructions))<small class="text-muted d-block mb-1">{{ $field->instructions }}</small>@endif
    <div class="row">
        @if(!empty($field->langs))
            @foreach($fields as $lang => $lang_fields)
                <div class="col lang-field js-lang-{{ $lang }}{{ $main_lang == $lang ? ' active_lang' : '' }}">
                    <div class="form-group">
                        @php $switch_id = 'fields'.(!empty($parent) ? str_replace(['[', ']'], '', $parent).(isset($iterator) ? $iterator : 0) : '').$lang.$field->slug; @endphp
                        <div class="d-flex align-items-center">
                            <div class="custom-control custom-switch custom-switch-success mr-1">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       name="fields[{{ $lang }}]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                                       value="1"
                                       id="{{ $switch_id }}"
                                       data-prefix="fields[{{ $lang }}]"
                                       data-name="{{ $field->slug }}"
                                       {{ !empty($fields[$lang][$key]->value) ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="{{ $switch_id }}"></label>
                            </div>
                            <label for="{{ $switch_id }}" class="mb-0 cursor-pointer">{{ $locales_names[$lang] }}</label>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="col">
                <div class="form-group">
                    @php $switch_id = 'fields'.(!empty($parent) ? str_replace(['[', ']'], '', $parent).(isset($iterator) ? $iterator : 0) : '').$field->slug; @endphp
                    <div class="custom-control custom-switch custom-switch-success">
                        <input type="checkbox"
                               class="custom-control-input"
                               name="fields[{{ isset($main_key) ? $main_key : 'all' }}]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                               value="1"
                               id="{{ $switch_id }}"
                               data-prefix="fields[{{ isset($main_key) ? $main_key : 'all' }}]"
                               data-name="{{ $field->slug }}"
                               {{ !empty($field->value) ? 'checked' : '' }}
                        >
                        <label class="custom-control-label" for="{{ $switch_id }}"></label>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>