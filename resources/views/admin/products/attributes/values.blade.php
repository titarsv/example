@php
if(!empty($attribute) && $attribute->is_numeric_values && !empty($languages)){
    $languages = [Config::get('app.locale') => trans('locale.Name')];
}
@endphp
<div class="row justify-content-between">
    @foreach($languages as $lang_key => $lang_name)
        <div class="col form-group">
            <label>{{ $lang_name }}</label>
        </div>
    @endforeach
    @if($attribute->is_filter)
    <div class="col form-group">
        <label>{{ trans('locale.Value') }}</label>
    </div>
    @endif
    @if(in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']))
    <div class="col form-group value-image">
        <label>{{ trans('locale.Image') }}</label>
    </div>
    @endif
    @if($me->hasAccess(['attributes.write']))
        <div class="col-md-2 form-group">

        </div>
    @endif
</div>
<div data-repeater-list="values">
    @if($attribute->values->count())
        @foreach($attribute->values as $key => $value)
            @include('admin.products.attributes.value')
        @endforeach
    @else
        <div class="row justify-content-between" data-repeater-item>
            <input type="hidden" name="values[0][value_id]" value="">
            @foreach($languages as $lang_key => $lang_name)
                <div class="col form-group">
                    @if(empty($attribute->unit) || empty($attribute->is_numeric_values))
                        <input type="text" name="values[0][name{{ count($languages) > 1 ? '_'.$lang_key : '' }}]" class="form-control{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' form-control-sm' }}" value="" placeholder="{{ $lang_name }}" autocomplete="off" />
                    @else
                        <fieldset>
                            <div class="input-group{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' input-group-sm' }}">
                                <input type="text" name="values[0][name{{ count($languages) > 1 ? '_'.$lang_key : '' }}]" class="form-control{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' form-control-sm' }}" value="" placeholder="{{ $lang_name }}" autocomplete="off" />
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
                    <input type="text" name="values[0][value]" class="form-control{{ in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']) ? '' : ' form-control-sm' }}" value="" placeholder="{{ trans('locale.Meaning') }}" />
                    <div class="help-block"></div>
                </div>
            @endif
            @if(in_array($attribute->type, ['multiple_color_checkboxes', 'single_color_radio']))
                <div class="col form-group value-image">
                    @include('admin.layouts.form.image', [
                     'key' => 'values[0][file_id]',
                     'image' => null
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
    @endif
</div>
