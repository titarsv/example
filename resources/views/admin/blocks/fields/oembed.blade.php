<div class="field-group"@if(!empty($field->conditional_field)) data-conditional-field="{{ $field->conditional_field }}" data-conditional-operator="{{ !empty($field->conditional_operator) ? $field->conditional_operator : '==' }}" data-conditional-value="{{ $field->conditional_value ?? '' }}"@endif>
    @if(!empty($field->langs))
        <div class="row">
            @foreach($fields as $lang => $lang_fields)
                <div class="col lang-field js-lang-{{ $lang }}{{ $main_lang == $lang ? ' active_lang' : '' }}">
                    <label>{{ $field->name }} {{ $locales_names[$lang] }}</label>
                    <div class="form-group">
                        <div class="image-container js_picture_wrapper">
                            <input type="hidden"
                                   id="fields{{ !empty($parent) ? str_replace(['[', ']'], '', $parent).(isset($iterator) ? $iterator : 0) : '' }}{{ $field->slug }}"
                                   name="fields[{{ $lang }}]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                                   value="{{ isset($fields[$lang][$key]->value) ? $fields[$lang][$key]->value['id'] : '' }}"
                                   data-prefix="fields[{{ $lang }}]"
                                   data-name="{{ $field->slug }}"
                            />
                            @if(!empty($field->value))
                                <div>
                                    <div>
                                        <div class="bar">
                                            <i class="bx bx-zoom-in js_zoom_image"></i>
                                            <i class="bx bxs-trash js_remove_image"></i>
                                        </div>
                                        <img src="{{ !empty($fields[$lang][$key]->value['image']) ? $fields[$lang][$key]->value['image']->url() : '/uploads/no_image.jpg' }}" />
                                    </div>
                                </div>
                                <div class="js_upload_image_button" data-type="single" style="display: none;">
                                    <div class="bx bx-images add-btn"></div>
                                </div>
                            @else
                                <div class="js_upload_image_button" data-type="single">
                                    <div class="bx bx-images add-btn"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <label>{{ $field->name }}</label>
            @if(!empty($field->instructions))<small class="text-muted d-block mb-1">{{ $field->instructions }}</small>@endif
        <div class="image-container js_picture_wrapper">
            <input type="hidden"
                   id="fields{{ !empty($parent) ? str_replace(['[', ']'], '', $parent).(isset($iterator) ? $iterator : 0) : '' }}{{ $field->slug }}"
                   name="fields[{{ isset($main_key) ? $main_key : 'all' }}]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                   value="{!! isset($field->value) ? $field->value['id'] : '' !!}"
                   data-prefix="fields[{{ isset($main_key) ? $main_key : 'all' }}]"
                   data-name="{{ $field->slug }}"
            />
            @if(!empty($field->value))
                <div>
                    <div>
                        <div class="bar">
                            <i class="bx bx-zoom-in js_zoom_image"></i>
                            <i class="bx bxs-trash js_remove_image"></i>
                        </div>
                        <img src="{{ !empty($field->value['image']) ? $field->value['image']->url() : '/uploads/no_image.jpg' }}" />
                    </div>
                </div>
                <div class="js_upload_image_button" data-type="single" style="display: none;">
                    <div class="bx bx-images add-btn"></div>
                </div>
            @else
                <div class="js_upload_image_button" data-type="single">
                    <div class="bx bx-images add-btn"></div>
                </div>
            @endif
        </div>
    @endif
</div>