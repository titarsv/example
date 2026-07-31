<div class="field-group">
    <label>{{ $field->name }}</label>
    <div class="row">
        @if(!empty($field->langs))
            @foreach($fields as $lang => $lang_fields)
                <div class="col lang-field js-lang-{{ $lang }}{{ $main_lang == $lang ? ' active_lang' : '' }}">
                    <div class="form-group">
                        <textarea class="form-control form-control-sm"
                                  name="fields[{{ $lang }}]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                                  placeholder="{{ $locales_names[$lang] }}"
                                  data-prefix="fields[{{ $lang }}]"
                                  data-name="{{ $field->slug }}"
                        >{{ isset($fields[$lang][$key]->value) ? $fields[$lang][$key]->value : '' }}</textarea>
                    </div>
                </div>
            @endforeach
        @else
            <div class="col">
                <div class="form-group">
                    <textarea class="form-control form-control-sm"
                              name="fields[all]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                              data-prefix="fields[all]"
                              data-name="{{ $field->slug }}"
                    >{{ isset($field->value) ? $field->value : '' }}</textarea>
                </div>
            </div>
        @endif
    </div>
</div>