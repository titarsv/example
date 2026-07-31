<div class="row justify-content-between" data-repeater-item>
    <input type="hidden" name="values[{{ $value->id }}][value_id]" value="{{ $value->id }}">
    @foreach($languages as $lang_key => $lang_name)
        <div class="col form-group">
            @if(empty($attribute->unit) || empty($attribute->is_numeric_values))
                <input type="text" name="values[{{ $value->id }}][name{{ count($languages) > 1 ? '_'.$lang_key : '' }}]" class="form-control{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' form-control-sm' }}" value="{{ $value->localize($lang_key, 'name') }}" placeholder="{{ $lang_name }}" autocomplete="off" />
            @else
                <fieldset>
                    <div class="input-group{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' input-group-sm' }}">
                        <input type="text" name="values[{{ $value->id }}][name{{ count($languages) > 1 ? '_'.$lang_key : '' }}]" class="form-control{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' form-control-sm' }}" value="{{ $value->localize($lang_key, 'name') }}" placeholder="{{ $lang_name }}" autocomplete="off" />
                        <div class="input-group-prepend">
                            <span class="input-group-text">{{ $attribute->unit }}</span>
                        </div>
                    </div>
                </fieldset>
            @endif
            <div class="help-block"></div>
        </div>
    @endforeach
    @if($attribute->is_filter)
    <div class="col form-group">
        <input type="text" name="values[{{ $value->id }}][value]" class="form-control{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' form-control-sm' }}" value="{{ $value->value }}" placeholder="{{ trans('locale.Meaning') }}" />
        <div class="help-block"></div>
    </div>
    @endif
    @if(in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']))
    <div class="col form-group value-image">
        @include('admin.layouts.form.image', [
         'key' => 'values['.$value->id.'][file_id]',
         'image' => $value->image
        ])
    </div>
    @endif
    @if($me->hasAccess(['attributes.write']))
    <div class="col-md-2 form-group">
        <button class="btn btn-danger{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' btn-sm' }} text-nowrap px-1" data-repeater-delete="" type="button"> <i class="bx bx-x"></i>
            {{ trans('locale.Delete') }}
        </button>
    </div>
    @endif
</div>