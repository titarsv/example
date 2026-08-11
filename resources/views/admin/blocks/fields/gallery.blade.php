<div class="field-group"@if(!empty($field->conditional_field)) data-conditional-field="{{ $field->conditional_field }}" data-conditional-operator="{{ !empty($field->conditional_operator) ? $field->conditional_operator : '==' }}" data-conditional-value="{{ $field->conditional_value ?? '' }}"@endif>
    <label>{{ $field->name }}</label>
            @if(!empty($field->instructions))<small class="text-muted d-block mb-1">{{ $field->instructions }}</small>@endif
    <div class="row">
        @if(!empty($field->langs))
            @foreach($fields as $lang => $lang_fields)
                <div class="col-12 lang-field js-lang-{{ $lang }}{{ $main_lang == $lang ? ' active_lang' : '' }}">
                    <label>{{ $locales_names[$lang] }}</label>
                    @php $gallery_parent = 'fields['.$lang.']'.(!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : ''); @endphp
                    <div class="row gallery-container">
                        @if(!empty($fields[$lang][$key]->value))
                            @foreach($fields[$lang][$key]->value as $image)
                                <div class="col-sm-3">
                                    <div class="js_gallery_picture_wrapper">
                                        <input name="{{ $gallery_parent }}[{{ $field->slug }}][]" data-name="{{ $field->slug }}[]" data-prefix="{{ $gallery_parent }}" value="{{ $image->id }}" type="hidden">
                                        <div class="bar">
                                            <i class="bx bx-zoom-in js_zoom_image"></i>
                                            <i class="bx bxs-trash js_remove_image"></i>
                                        </div>
                                        <img src="{{ $image->url() }}">
                                        @if($image->type == 'video')
                                            <i class="bx bx-play-circle"></i>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                        <div class="col-sm-3 add-gallery-image js_upload_image_button" data-name="{{ $field->slug }}[]" data-parent="{{ $gallery_parent }}" data-type="multiple">
                            <div class="bx bx-images add-btn"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            @php $gallery_parent = 'fields['.(isset($main_key) ? $main_key : 'all').']'.(!empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : ''); @endphp
            <div class="col-12">
                <div class="row gallery-container">
                    @if(!empty($field->value))
                        @foreach($field->value as $image)
                            <div class="col-sm-3">
                                <div class="js_gallery_picture_wrapper">
                                    <input name="{{ $gallery_parent }}[{{ $field->slug }}][]" data-name="{{ $field->slug }}[]" data-prefix="{{ $gallery_parent }}" value="{{ $image->id }}" type="hidden">
                                    <div class="bar">
                                        <i class="bx bx-zoom-in js_zoom_image"></i>
                                        <i class="bx bxs-trash js_remove_image"></i>
                                    </div>
                                    <img src="{{ $image->url() }}">
                                    @if($image->type == 'video')
                                        <i class="bx bx-play-circle"></i>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                    <div class="col-sm-3 add-gallery-image js_upload_image_button" data-name="{{ $field->slug }}[]" data-parent="{{ $gallery_parent }}" data-type="multiple">
                        <div class="bx bx-images add-btn"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>