<div class="field-group">
    <label>{{ $field->name }}</label>
    <div class="row">
        @if(!empty($field->langs))
            @foreach($fields as $lang => $lang_fields)
                <div class="col lang-field js-lang-{{ $lang }}{{ $main_lang == $lang ? ' active_lang' : '' }}">
                    <div class="form-group">
                    <input type="text"
                               class="form-control form-control-sm"
                               name="fields[{{ $lang }}]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                               value="{{ isset($fields[$lang][$key]->value) ? $fields[$lang][$key]->value : '' }}"
                               placeholder="{{ $locales_names[$lang] }}"
                               data-prefix="fields[{{ $lang }}]"
                               data-name="{{ $field->slug }}"
                        />
                    </div>
                </div>
            @endforeach
        @else
            <div class="col">
                <div class="form-group">
                    <input type="text"
                           class="form-control form-control-sm"
                           name="fields[{{ isset($main_key) ? $main_key : 'all' }}]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                           value="{!! isset($field->value) ? $field->value : '' !!}"
                           data-prefix="fields[{{ isset($main_key) ? $main_key : 'all' }}]"
                           data-name="{{ $field->slug }}"
                    />
                </div>
            </div>
        @endif
    </div>
</div>